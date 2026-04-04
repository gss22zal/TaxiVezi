<?php

namespace App\Console\Commands;

use App\Models\DailyRevenue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateDailyRevenues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revenue:calculate 
                            {--days=7 : Количество дней для расчёта}
                            {--all : Пересчитать все дни с начала работы системы}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Рассчитывает и сохраняет ежедневную выручку в БД';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = \App\Models\Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone);

        if ($this->option('all')) {
            // Получаем первую дату заказа
            $firstOrder = DB::table('orders')
                ->min('created_at');

            if (!$firstOrder) {
                $this->info('Заказов пока нет.');
                return Command::SUCCESS;
            }

            $startDate = Carbon::parse($firstOrder)->timezone($timezone);
            $days = $startDate->diffInDays($now) + 1;
            
            $this->info("Пересчёт всех дней с {$startDate->toDateString()} ({$days} дней)...");
        } else {
            $days = (int) $this->option('days');
            $startDate = $now->copy()->subDays($days - 1);
        }

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            DailyRevenue::updateForDate($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Готово! Данные за {$days} дней сохранены.");

        return Command::SUCCESS;
    }
}
