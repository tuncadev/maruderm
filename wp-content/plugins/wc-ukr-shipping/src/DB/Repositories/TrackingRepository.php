<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUSCore\Facades\DB;

class TrackingRepository
{
    public function findActiveTrackingRecords(?int $cursorId, int $limit): ?array
    {
        $query = DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->where('tracking_active', 1);

        if ($cursorId !== null) {
            $query->where('id', '>', $cursorId);
        }

        return $query->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
