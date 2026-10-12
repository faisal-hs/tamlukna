<?php

namespace App\Enums;

/**
 * Street-facing side of the property (الواجهة).
 */
enum Facade: string
{
    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
    case NorthEast = 'north_east';
    case NorthWest = 'north_west';
    case SouthEast = 'south_east';
    case SouthWest = 'south_west';
}
