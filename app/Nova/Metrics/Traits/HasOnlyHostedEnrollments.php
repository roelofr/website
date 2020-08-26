<?php

declare(strict_types=1);

namespace App\Nova\Metrics\Traits;

use App\Models\Activity;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\NovaRequest;

trait HasOnlyHostedEnrollments
{
    /**
     * Returns a query for all enrollments this user can see
     * @param NovaRequest $request
     * @return Builder
     */
    protected function getHostedEnrollmentsQuery(NovaRequest $request): Builder
    {
        // Get user
        $user = $request->user();

        // Get base query
        $query = Enrollment::query();

        // User is admin, don't filter
        if ($user->can('admin', ActivityModel::class)) {
            return $query;
        }

        // User only has a subset of queries, filter it
        return $query->whereIn('activity_id', $user->getHostedActivityQuery(Activity::query())->select('id'));
    }

    /**
     * @inheritdoc
     */
    protected function getCacheKey(NovaRequest $request)
    {
        return sprintf('%s.%s', parent::getCacheKey($request), $request->user()->id);
    }
}
