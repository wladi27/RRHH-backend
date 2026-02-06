<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParametroFormulario;
use Illuminate\Support\Facades\DB;

class BackfillParametros extends Command
{
    protected $signature = 'parametros:backfill';

    protected $description = 'Normaliza parametros_formularios al esquema actual';

    public function handle(): int
    {
        $this->info('Iniciando backfill de parametros...');

        $parametros = ParametroFormulario::all();
        $bar = $this->output->createProgressBar($parametros->count());
        $bar->start();

        foreach ($parametros as $p) {
            DB::transaction(function () use ($p, &$bar) {
                $updated = false;

                if (empty($p->data_type)) { $p->data_type = 'text'; $updated = true; }
                if (is_null($p->required)) { $p->required = false; $updated = true; }
                if (is_null($p->order)) { $p->order = 0; $updated = true; }

                if ($updated) { $p->save(); }

                $bar->advance();
            });
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Backfill completado.');

        return 0;
    }
}
