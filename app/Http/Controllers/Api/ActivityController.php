<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Facades\Enroll;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivityRegisterRequest;
use App\Http\Resources\Api\ActivityResource;
use App\Models\Activity;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Response;
use Throwable;

class ActivityController extends Controller
{
    public function __construct()
    {
        // Ensure JSON is supported
        $this->middleware('accept-json');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $wantsPast = (bool) $request->input('past', false);

        $activities = Activity::query()
            ->whereAvailable($user)
            ->wherePublished()
            ->where('end_date', $wantsPast ? '<' : '>=', Date::now())
            ->paginate()
            ->appends('past', (int) $wantsPast);

        return ActivityResource::collection($activities)->toResponse($request);
    }

    /**
     * Display the given activity and the corresponding ticket(s).
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        $activity = Activity::query()
            ->whereAvailable($user)
            ->wherePublished()
            ->whereSlug($slug)
            ->firstOrFail();

        abort_unless($activity, HttpResponse::HTTP_NOT_FOUND);

        return ActivityResource::make($activity)->toResponse($request);
    }

    /**
     * Tries to register the user for the activity with the given slug, using the ticket specified
     * in the body.
     */
    public function register(ActivityRegisterRequest $request, string $slug): JsonResponse
    {
        $user = $request->user();

        /** @var Activity */
        $activity = Activity::query()
            ->whereAvailable($user)
            ->wherePublished()
            ->with('tickets', fn ($query) => $query->where('id', $request->ticket))
            ->firstWhere('slug', $slug);

        abort_unless($activity, HttpResponse::HTTP_NOT_FOUND);

        /** @var Ticket */
        $chosenTicket = $activity->tickets->first();
        abort_unless($chosenTicket, HttpResponse::HTTP_NOT_FOUND);
        abort_unless($chosenTicket->isAvailableFor($user), HttpResponse::HTTP_BAD_REQUEST);

        if (! Enroll::canEnroll($activity)) {
            abort(HttpResponse::HTTP_BAD_REQUEST, 'You cannot enroll in this activity.');
        }

        try {
            $enrollment = Enroll::createEnrollment($activity, $chosenTicket);
        } catch (Throwable) {
            //
        }

        return Response::json(ActivityResource::make($activity));
    }
}
