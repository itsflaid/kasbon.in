<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('debtors')
            ->select('warung_id', 'normalized_name')
            ->selectRaw('MIN(id) as keep_id')
            ->groupBy('warung_id', 'normalized_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $duplicateIds = DB::table('debtors')
                ->where('warung_id', $duplicate->warung_id)
                ->where('normalized_name', $duplicate->normalized_name)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('entries')
                ->whereIn('debtor_id', $duplicateIds)
                ->update(['debtor_id' => $duplicate->keep_id]);

            DB::table('debtors')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }

    public function down(): void
    {
        //
    }
};
