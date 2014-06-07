<?php

namespace PloufPlouf;

/**
 * Class Randomizer
 */
class Randomizer {

    /**
     * Pick a random index from an array
     *
     * @param array $values
     * @return false|int
     */
    public function pick(array $values) {

        $keys = array_keys($values);
        $nbOfKeys = count($keys);

        if ($nbOfKeys === 0) { return false; }

        // Time is a flat circle and eventually, it all comes down to that line ...
        return mt_rand(0, ($nbOfKeys -1));
    }
}