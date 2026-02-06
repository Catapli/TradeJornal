<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\EconomicEvent;
use Carbon\Carbon;

class PropFirmService
{
    public function validate(Trade $trade)
    {
        // 1. Obtener reglas de la cuenta
        // Asumiendo ruta: Trade -> Account -> ProgramLevel -> ProgramObjective -> rules_metadata
        // Si tu relación es diferente, ajusta esta línea:
        $metadata = $trade->account->programLevel?->objective?->rules_metadata;

        // Si no hay reglas definidas, salimos
        if (!$metadata || !isset($metadata['restrictions'])) return;

        $rules = $metadata['restrictions'];

        // 2. Limpiamos violaciones previas para no duplicar si se actualiza el trade
        $trade->violations()->delete();

        // 3. REGLA 1: Duración Mínima (Ej: 1 minuto)
        if (isset($rules['min_trade_duration_seconds']) && $trade->exit_time) {
            $duration = $trade->entry_time->diffInSeconds($trade->exit_time);
            $minDuration = $rules['min_trade_duration_seconds'];

            if ($duration < $minDuration) {
                $trade->violations()->create([
                    'rule_key' => 'min_duration',
                    'message' => "Operación cerrada en {$duration}s. El mínimo es {$minDuration}s."
                ]);
            }
        }

        // 4. REGLA 2: Noticias (News Trading)
        if (isset($rules['no_news_trading'])) {
            $this->checkNews($trade, $rules['no_news_trading']);
        }
    }

    private function checkNews(Trade $trade, array $newsRules)
    {
        $minutesBefore = $newsRules['minutes_before'] ?? 2;
        $minutesAfter = $newsRules['minutes_after'] ?? 2;

        // Definimos la ventana prohibida alrededor de la ENTRADA del trade
        $forbiddenStart = $trade->entry_time->copy()->subMinutes($minutesBefore);
        $forbiddenEnd = $trade->entry_time->copy()->addMinutes($minutesAfter);

        // Buscamos si hubo una noticia ROJA en esa ventana
        // Asegúrate de que tu modelo EconomicEvent tenga los campos correctos de fecha/hora
        $news = EconomicEvent::where('impact', 'high')
            ->whereBetween('date', [ // Ajustar si tu fecha/hora están separadas o juntas
                $forbiddenStart->toDateString(),
                $forbiddenEnd->toDateString()
            ])
            ->get()
            ->filter(function ($event) use ($forbiddenStart, $forbiddenEnd) {
                // Combinamos fecha y hora del evento para comparar con Carbon
                $eventTime = Carbon::parse($event->date . ' ' . $event->time);
                return $eventTime->between($forbiddenStart, $forbiddenEnd);
            })
            ->first();

        if ($news) {
            $trade->violations()->create([
                'rule_key' => 'news_trading',
                'message' => "Entrada durante noticia de alto impacto: {$news->event} ({$news->currency})"
            ]);
        }
    }
}
