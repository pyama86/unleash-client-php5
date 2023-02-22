<?php

namespace Unleash\Stickiness;
use lastguest\Murmur;


class MurmurHashCalculator
{
    public function calculate($id, $groupId, $normalizer = 100)
    {
        return murmurhash3_int("{$groupId}:{$id}") % $normalizer + 1;
    }
}
