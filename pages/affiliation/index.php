<?php

/**
 * @defgroup pages_affiliation Affiliation Pages
 */

/**
 * @file pages/affiliation/index.php
 *
 * Copyright (c) 2023-2026 Sasz Kolomon
 *
 * @ingroup pages_affiliation
 *
 * @brief Handle requests for the per-organization publication list:
 *   /index.php/{server}/affiliation?org_id=22
 *   /index.php/{server}/affiliation?profile_ins=1114
 */

switch ($op) {
    case 'index':
        return new \PKP\pages\affiliation\AffiliationHandler();
}
