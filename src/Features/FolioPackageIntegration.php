<?php

namespace Polynds\ThinkphpSentry\Features;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Laravel\Folio\Events\ViewMatched;
use Laravel\Folio\Folio;
use Laravel\Folio\MountPath;
use Laravel\Folio\Pipeline\MatchedView;
use Polynds\ThinkphpSentry\Integration;
use Polynds\ThinkphpSentry\Tracing\Middleware;
use Sentry\Breadcrumb;
use Sentry\SentrySdk;
use Sentry\Tracing\TransactionSource;

class FolioPackageIntegration extends Feature
{
    private const FEATURE_KEY = 'folio';

    public function isApplicable(): bool
    {
        return class_exists(Folio::class);
    }

    public function onBoot(Dispatcher $events): void
    {
        $events->listen(ViewMatched::class, [$this, 'handleViewMatched']);
    }

    public function handleViewMatched(ViewMatched $matched): void
    {
        Middleware::signalRouteWasMatched();

        $routeName = $this->extractRouteForMatchedView($matched->matchedView, $matched->mountPath);

        Integration::addBreadcrumb(new Breadcrumb(
            Breadcrumb::LEVEL_INFO,
            Breadcrumb::TYPE_NAVIGATION,
            'folio.route',
            $routeName
        ));

        Integration::setTransaction($routeName);

        $transaction = SentrySdk::getCurrentHub()->getTransaction();

        if ($transaction === null || !$transaction->getSampled()) {
            return;
        }

        $transaction->setName($routeName);
        $transaction->getMetadata()->setSource(TransactionSource::route());
    }

    private function extractRouteForMatchedView(MatchedView $matchedView, MountPath $mountPath): string
    {
        $path = Str::beforeLast('/' . ltrim($mountPath->baseUri . $matchedView->relativePath(), '/'), '.blade.php');

        return Str::replace(['[', ']'], ['{', '}'], $path);
    }
}
