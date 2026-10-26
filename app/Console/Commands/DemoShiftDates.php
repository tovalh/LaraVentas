<?php

namespace App\Console\Commands;

use App\Support\Demo\RollingDates;
use Illuminate\Console\Command;

class DemoShiftDates extends Command
{
    protected $signature = 'demo:shift-dates';

    protected $description = 'Corre las fechas de la data demo para que la venta más reciente sea de hoy';

    public function handle(): int
    {
        $dias = RollingDates::shift();
        $this->info($dias > 0 ? "Fechas desplazadas {$dias} día(s)." : 'La data demo ya está al día.');

        return self::SUCCESS;
    }
}
