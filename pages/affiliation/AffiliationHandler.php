<?php

/**
 * @file pages/affiliation/AffiliationHandler.php
 *
 * Copyright (c) 2023-2026 Sasz Kolomon
 *
 * @class AffiliationHandler
 *
 * @ingroup pages_affiliation
 *
 * @brief Public list of published preprints where at least one author of the
 *   current version is affiliated with an organization from the RIT NOD list.
 *
 *   /index.php/{server}/affiliation?org_id=22
 *   /index.php/{server}/affiliation?profile_ins=1114
 *   ...&page=2 for the following pages
 */

namespace PKP\pages\affiliation;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\PKPRequest;
use PKP\facades\Locale;
use PKP\ritNod\RitNodOrganizations;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;

class AffiliationHandler extends Handler
{
    /** Locale of author_settings.affiliation that holds the English organization name */
    public const AFFILIATION_LOCALE = 'en';

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));
        if (class_exists('APP\security\authorization\OpsServerMustPublishPolicy')) {
            $this->addPolicy(new \APP\security\authorization\OpsServerMustPublishPolicy($request));
        }
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Show the publications of one organization.
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function index($args, $request)
    {
        $context = $request->getContext();

        // Resolve the organization from ?org_id= or ?profile_ins=
        $key = null;
        foreach ([RitNodOrganizations::KEY_ORG_ID, RitNodOrganizations::KEY_PROFILE_INS] as $candidate) {
            $value = $request->getUserVar($candidate);
            if ($value !== null && $value !== '') {
                $key = $candidate;
                break;
            }
        }
        if (!$key || !ctype_digit((string) $value)) {
            $request->getDispatcher()->handle404();
            exit;
        }
        $keyValue = (int) $value;

        $organizations = RitNodOrganizations::distinctOrganizations(
            RitNodOrganizations::findBy($key, $keyValue)
        );
        if (empty($organizations)) {
            $request->getDispatcher()->handle404();
            exit;
        }

        $affiliationNames = array_map(
            fn (array $org) => RitNodOrganizations::normalizeName($org['full_name_inst_en']),
            $organizations
        );

        // Pagination (?page=N, first page has no parameter)
        $pageVar = $request->getUserVar('page');
        if ($pageVar !== null && (!ctype_digit((string) $pageVar) || (int) $pageVar < 1)) {
            $request->getDispatcher()->handle404();
            exit;
        }
        $page = $pageVar === null ? 1 : (int) $pageVar;
        $count = (int) ($context->getData('itemsPerPage') ?: Config::getVar('interface', 'items_per_page'));
        $offset = ($page - 1) * $count;

        $collector = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->filterByAuthorAffiliations($affiliationNames, self::AFFILIATION_LOCALE)
            ->orderBy(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_DESC);

        $total = $collector->getCount();
        $submissions = $collector->limit($count)->offset($offset)->getMany()->toArray();

        if ($page > 1 && !count($submissions)) {
            $request->getDispatcher()->handle404();
            exit;
        }

        $showingStart = $offset + 1;
        $showingEnd = $offset + count($submissions);
        $nextPage = $total > $showingEnd ? $page + 1 : null;
        $prevPage = $page > 1 ? $page - 1 : null;

        $pageUrl = fn (int $p) => $request->url(
            null,
            'affiliation',
            null,
            null,
            $p > 1 ? [$key => $keyValue, 'page' => $p] : [$key => $keyValue]
        );

        $locale = Locale::getLocale();
        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $organizationNames = array_map(
            fn (array $org) => RitNodOrganizations::getDisplayName($org, $locale),
            $organizations
        );
        $templateMgr->assign([
            'organizationNames' => $organizationNames,
            'pageTitle' => implode('; ', $organizationNames),
            'preprints' => $submissions,
            'showingStart' => $showingStart,
            'showingEnd' => $showingEnd,
            'total' => $total,
            'prevUrl' => $prevPage ? $pageUrl($prevPage) : null,
            'nextUrl' => $nextPage ? $pageUrl($nextPage) : null,
            'authorUserGroups' => Repo::userGroup()->getCollector()
                ->filterByRoleIds([Role::ROLE_ID_AUTHOR])
                ->filterByContextIds([$context->getId()])
                ->getMany()
                ->remember(),
        ]);

        $templateMgr->display('frontend/pages/affiliation.tpl');
    }
}
