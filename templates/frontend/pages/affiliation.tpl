{**
 * templates/frontend/pages/affiliation.tpl
 *
 * Copyright (c) 2023-2026 Sasz Kolomon
 *
 * @brief Published preprints with at least one author affiliated with an organization.
 *
 * @uses $organizationNames array Display names of the matched organization(s)
 * @uses $pageTitle string Organization name(s) joined for the <title>
 * @uses $preprints array List of Submission objects
 * @uses $showingStart int
 * @uses $showingEnd int
 * @uses $total int
 * @uses $prevUrl string|null
 * @uses $nextUrl string|null
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle|escape}

<div class="page page_affiliation">
	<h1 class="page_title">
		{if $organizationNames|@count == 1}
			{$organizationNames[0]|escape}
		{else}
			{foreach from=$organizationNames item=organizationName name=orgNames}
				{$organizationName|escape}{if !$smarty.foreach.orgNames.last};<br><br>{/if}
			{/foreach}
		{/if}
	</h1>

	<h3 class="page_title">
		<div class="preprint_count">
			{translate key="catalog.browseTitles" numTitles=$total}
		</div>
	</h3>
	<hr>

	{if $preprints|@count}
		<ul class="cmp_preprint_list">
			{foreach from=$preprints item=preprint}
				<li>
					{include file="frontend/objects/preprint_summary.tpl" section=null showDatePublished=true}
				</li>
			{/foreach}
		</ul>

		{include
			file="frontend/components/pagination.tpl"
			prevUrl=$prevUrl
			nextUrl=$nextUrl
			showingStart=$showingStart
			showingEnd=$showingEnd
			total=$total
		}
	{else}
		<p class="section_empty">
			{translate key="search.noResults"}
		</p>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
