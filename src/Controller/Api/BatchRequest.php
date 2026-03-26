<?php

namespace App\Controller\Api;

/**
 * Small helper for batch endpoints.
 *
 * Accepts any JSON payload and extracts a de-duplicated list of positive ints
 * from the `ids` field.
 */
final class BatchRequest
{
    /**
     * @return int[]
     */
    public static function idsFromPayload(mixed $payload): array
    {
        if (!is_array($payload) || !isset($payload['ids']) || !is_array($payload['ids'])) {
            return [];
        }

        $ids = [];
        foreach ($payload['ids'] as $id) {
            // Cast strings/numbers to int, drop invalid ids (0, negative, non-numeric).
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
