<?php

namespace App\Enums;

enum DataDeletionScope: string
{
    case All = 'all';
    case Selected = 'selected';
    case Read = 'read';
    case Older = 'older';
}
