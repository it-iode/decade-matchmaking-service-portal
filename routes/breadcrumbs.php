<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

try {
    Breadcrumbs::for('admin.dashboard.index', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('Dashboard', route('admin.dashboard.index'));
    });
    Breadcrumbs::for('user.home', function (BreadcrumbTrail $trail) {
        $trail->push('Home', route('user.home'));
    });
    if (\Illuminate\Support\Facades\Route::has('admin.sample-data.index')) {
        Breadcrumbs::for('admin.sample-data.index', function (BreadcrumbTrail $trail) {
            $trail->parent('admin.dashboard.index');
            $trail->push('Sample data', route('admin.sample-data.index'));
        });
    }

    // Offer Breadcrumbs section
    Breadcrumbs::for('admin.offer.list', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('Offers list', route('admin.offer.list'));
    });
    Breadcrumbs::for('admin.offer.show', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('Offers list', route('admin.offer.list'));
    });

    // Request Breadcrumbs section
    Breadcrumbs::for('request.edit', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('List of my request', route('request.me.list'));
        $trail->push('Request #'.request('id'), route('request.edit', request('id')));
    });
    Breadcrumbs::for('request.show', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('List of my request', route('request.me.list'));
        $trail->push('Request #'.request('id'), route('request.show', request('id')));
    });
    Breadcrumbs::for('request.me.list', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('List of my request', route('request.me.list'));
    });
    Breadcrumbs::for('request.me.subscribed-requests', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('My subscribed Requests', route('request.me.subscribed-requests'));
    });
    Breadcrumbs::for('request.me.matched-requests', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('My Matched Requests', route('request.me.matched-requests'));
    });
    Breadcrumbs::for('request.create', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('List of my request', route('request.me.list'));
        $trail->push('Submit new request', route('request.create'));
    });

    // Opportunity Breadcrumbs section
    Breadcrumbs::for('opportunity.create', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('Submit a new Opportunity', route('opportunity.create'));
    });

    Breadcrumbs::for('me.opportunity.list', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('My submitted opportunities', route('me.opportunity.list'));
    });
    Breadcrumbs::for('opportunity.list', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('View and Apply for Partner Opportunities', route('opportunity.list'));
    });
    Breadcrumbs::for('me.opportunity.show', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('My submitted opportunities', route('me.opportunity.list'));
        $trail->push('My Opportunity details', route('me.opportunity.show', request('opportunity')));
    });
    Breadcrumbs::for('opportunity.show', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('View and Apply for Partner Opportunities', route('opportunity.list'));
        $trail->push('Opportunity details', route('opportunity.show', request('opportunity')));
    });
    Breadcrumbs::for('opportunity.edit', function (BreadcrumbTrail $trail) {
        $trail->parent('user.home');
        $trail->push('My submitted opportunities', route('me.opportunity.list'));
        $trail->push('Edit Opportunity', route('opportunity.edit', request('opportunity')));
    });
} catch (\Diglactic\Breadcrumbs\Exceptions\DuplicateBreadcrumbException $e) {

}
