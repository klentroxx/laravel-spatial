<?php

namespace ASanikovich\LaravelSpatial\Enums;

enum AxisOrder: string
{
    case SridDefined = 'srid-defined';
    case LatLong = 'lat-long';
    case LongLat = 'long-lat';
}