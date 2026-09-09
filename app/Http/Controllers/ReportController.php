<?php

namespace App\Http\Controllers;

use App\Services\WeeklyReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function weekly(Request $request, WeeklyReportBuilder $builder): Response
    {
        abort_unless($request->user()?->isTechnician(), 403);

        $report = $builder->build();

        $pdf = Pdf::loadView('reports.weekly', ['report' => $report])
            ->setPaper('a4');

        $filename = 'rapport_hebdo_'.$report['period']['from']->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
