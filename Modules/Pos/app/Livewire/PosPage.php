<?php

namespace Modules\Pos\Livewire;

use Modules\Sales\Livewire\SalesPage;

class PosPage extends SalesPage
{
    protected string $module = 'Pos';

    protected string $permission = 'pos.view';

    protected string $writePermission = 'pos.manage';

    protected string $channel = 'pos';
}
