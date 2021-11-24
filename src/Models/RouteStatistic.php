<?php

namespace Bilfeldt\LaravelRouteStatistics\Models;

use Bilfeldt\RequestLogger\Contracts\RequestLoggerInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class RouteStatistic extends Model implements RequestLoggerInterface
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $dates = ['date'];

    //======================================================================
    // ACCESSORS
    //======================================================================

    //======================================================================
    // MUTATORS
    //======================================================================

    //======================================================================
    // SCOPES
    //======================================================================

    //======================================================================
    // RELATIONS
    //======================================================================

    public function account(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\\User');
    }

    //======================================================================
    // METHODS
    //======================================================================

    public function log(
        Request $request,
        $response,
        ?int $time = null,
        ?int $memory = null
    ): void {
        if (
            $route =
                optional($request->route())->getName() ?? optional($request->route())->uri()
        ) {
            static::firstOrCreate(
                [
                    'account_id' => optional($request->user())->id,
                    'user_id' => optional($request->user())->context_id,
                    'method' => $request->getMethod(),
                    'route' => $route,
                    'status' => $response->getStatusCode(),
                    'ip' => $request->ip(),
                    'date' => $this->getDate(),
                ],
                ['counter' => 0]
            )->increment('counter', 1);
        }
    }

    protected function getDate()
    {
        $date = Date::now();
        $aggregate = config('route-statistics.aggregate');

        if ($aggregate && !in_array($aggregate, ['YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE'])) {
            throw new \OutOfBoundsException('Invalid date aggregation');
        }

        if (in_array($aggregate, ['YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE'])) {
            $date->setSecond(0);
        }

        if (in_array($aggregate, ['YEAR', 'MONTH', 'DAY', 'HOUR'])) {
            $date->setMinute(0);
        }

        if (in_array($aggregate, ['YEAR', 'MONTH', 'DAY'])) {
            $date->setHour(0);
        }

        if (in_array($aggregate, ['YEAR', 'MONTH'])) {
            $date->setDay(1);
        }

        if (in_array($aggregate, ['YEAR'])) {
            $date->setMonth(1);
        }

        return $date;
    }

    protected static function newFactory()
    {
        return \Bilfeldt\LaravelRouteStatistics\Database\Factories\RouteStatisticFactory::new();
    }
}
