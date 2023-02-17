<?php

namespace App\Http\Controllers\User;

use App\Exports\BagsReportExport;
use App\Exports\DriversReportExport;
use App\Exports\StopsSolutionExport;
use App\Exports\StopsSummaryExport;
use App\Exports\ProjectRouteExport;
use App\Exports\ProjectsReportExport;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    private $projectRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function index(Request $request)
    {
        $projects = $this->projectRepository->getAll($request->all());

        return response()->json([
            'success'   => true,
            'data'      => $projects
        ]);
    }

    public function show($id)
    {
        $project = $this->projectRepository->getById($id);

        return response()->json([
            'success'   => true,
            'data'      => $project
        ]);
    }

    public function store(Request $request)
    {
        $project = $this->projectRepository->create($request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Successful registration',
            'data'      => $project
        ]);
    }

    public function update(Request $request, $id)
    {
        $project = $this->projectRepository->update($id, $request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Updated successfully',
            'data'      => $project
        ]);
    }

    public function delete($id)
    {
        $this->projectRepository->delete($id);

        return response()->json([
            'success'   => true,
            'message'   => 'Successful deleted'
        ]);
    }

    public function fullList(Request $request)
    {
        $projects = $this->projectRepository->getAll($request->all(), false);

        return response()->json([
            'success'   => true,
            'data'      => $projects
        ]);
    }

    public function clone(Request $request, $id)
    {
        $project = $this->projectRepository->clone($id, $request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Project Cloned',
            'data'      => $project
        ]);
    }

    public function addProjectDriver(Request $request, $project_id, $driver_id)
    {
        $project = $this->projectRepository->addProjectDriver($project_id, $driver_id, $request->all());

        return response()->json([
            'success'   => true,
            'message'   => 'Driver added to route plan',
            'data'      => $project
        ]);
    }

    public function updateProjectDriver(Request $request, $project_id, $driver_id)
    {
        $project = $this->projectRepository->updateProjectDriver($project_id, $driver_id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Driver updated to route plan',
            'data' => $project
        ]);
    }

    public function deleteProjectDriver($project_id, $driver_id)
    {
        $project = $this->projectRepository->deleteProjectDriver($project_id, $driver_id);

        return response()->json([
            'success' => true,
            'message' => 'Driver removed from route plan',
            'data' => $project
        ]);
    }

    public function deleteProjectStop($id, $stop_id)
    {
        $project = $this->projectRepository->deleteProjectStop($id, $stop_id);

        return response()->json([
            'success' => true,
            'message' => 'Stop removed from route plan',
            'data' => $project
        ]);
    }

    public function optimize($id)
    {
        $project = $this->projectRepository->optimize($id);

        return response()->json([
            'success'   => true,
            'message'   => 'successfully optimized',
            'data'      => $project
        ]);
    }

    public function dispatch($id)
    {
        $project = $this->projectRepository->dispatch($id);

        return response()->json([
            'success' => true,
            'message' => 'successfully dispatched',
            'data' => $project
        ]);
    }

    public function reverseRoute(Request $request, $id)
    {
        $project = $this->projectRepository->reverseRoute($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Route changed successfully',
            'data' => $project
        ]);
    }

    public function reorder(Request $request, $id)
    {
        $project = $this->projectRepository->reorder($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'successfully reordered',
            'data' => $project
        ]);
    }

    public function swapRoute(Request $request, $id)
    {
        $project = $this->projectRepository->swapRoute($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Route changed successfully',
            'data' => $project
        ]);
    }

    public function sms($project, $driver)
    {
        $this->projectRepository->sendSMS($project, $driver);

        return response()->json([
            'success' => true,
            'message' => 'SMS sent'
        ]);
    }

    public function smsAll($project)
    {
        $this->projectRepository->sendSMSAll($project);

        return response()->json([
            'success' => true,
            'message' => 'SMS sent'
        ]);
    }

    public function downloadSummary($id)
    {
        $summary = $this->projectRepository->summaryExport($id);

        return Excel::download(new StopsSummaryExport($summary), 'stops.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function downloadSolution($id)
    {
        $solution = $this->projectRepository->solutionExport($id);

        return Excel::download(new StopsSolutionExport($solution), 'stops.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function downloadRoute($id)
    {
        $route = $this->projectRepository->routeExport($id);

        return Excel::download(new ProjectRouteExport($route), 'stops.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function report(Request $request)
    {
        $report = $this->projectRepository->report($request->all());

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function reportDownload(Request $request)
    {
        $resume = $this->projectRepository->report($request->all());

        $details = $this->projectRepository->reportDetails($request->all());

        $percent = $this->projectRepository->reportPercent($request->all());

        return Excel::download(new ProjectsReportExport($resume, $details, $percent), 'report.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function bagsReport(Request $request)
    {
        $report = $this->projectRepository->bagsReport($request->all());

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function bagsReportDownload(Request $request)
    {
        $resume = $this->projectRepository->bagsReport($request->all());

        $details = $this->projectRepository->bagsReportDetails($request->all());

        return Excel::download(new BagsReportExport($resume, $details), 'report.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function driversReport(Request $request)
    {
        $report = $this->projectRepository->driversReport($request->all());

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function driversReportDownload(Request $request)
    {
        $resume = $this->projectRepository->driversReport($request->all());

        $details = $this->projectRepository->driversReportDetails($request->all());

        return Excel::download(new DriversReportExport($resume, $details), 'report.xls', 'Xls', ['Content-Type' => 'application/excel']);
    }

    public function deliveries(Request $request)
    {
        $deliveries = $this->projectRepository->deliveries($request->all());

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }
}
