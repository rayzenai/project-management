<?php

namespace RayzenAI\ProjectManagement\Support;

class MentionParser
{
    /**
     * Extract unique member ids from canonical mention tokens
     * of the form @[Display Name](member:ID).
     *
     * @return list<int>
     */
    public static function memberIds(string $body): array
    {
        preg_match_all('/@\[[^\]]+\]\(member:(\d+)\)/', $body, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }
}
