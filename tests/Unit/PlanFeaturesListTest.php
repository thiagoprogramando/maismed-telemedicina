<?php

namespace Tests\Unit;

use App\Models\Plan;
use Tests\TestCase;

class PlanFeaturesListTest extends TestCase
{
    public function test_features_list_decodes_json_array_and_formats_labels(): void
    {
        $plan = new Plan();
        $plan->features = json_encode(['Filho(a)', 'Neto(a)', 'Sogro(a)', 'Genro', 'Nora'], JSON_UNESCAPED_UNICODE);

        $this->assertSame([
            'Filho / Filha',
            'Neto / Neta',
            'Sogro / Sogra',
            'Genro',
            'Nora',
        ], $plan->featuresList());
    }

    public function test_description_bullets_split_pipe_separated_text(): void
    {
        $plan = new Plan();
        $plan->description = 'Até 4 familiares | 1 ano de acesso | Consultas ilimitadas';

        $this->assertSame([
            'Até 4 familiares',
            '1 ano de acesso',
            'Consultas ilimitadas',
        ], $plan->descriptionBullets());
    }
}
