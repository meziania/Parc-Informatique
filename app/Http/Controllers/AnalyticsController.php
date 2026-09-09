<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsBuilder $builder): Response
    {
        abort_unless($request->user()?->isTechnician(), 403);

        $days = (int) $request->integer('days', 30);
        if (! in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        return Inertia::render('Analytics/Index', [
            'analytics' => $builder->build($days),
            'filters' => [
                'days' => $days,
            ],
        ]);
    }
}
