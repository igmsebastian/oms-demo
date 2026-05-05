<?php

namespace App\Enums;

enum InventoryChangeType: string
{
    case Addition = 'addition';
    case Deduction = 'deduction';
    case Restore = 'restore';
    case Adjustment = 'adjustment';
}
