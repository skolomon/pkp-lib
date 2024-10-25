{**
 * templates/user/agreementForm.tpl
 *
 * Copyright (c) 2023 Sasz Kolomon
 *
 * Accession Agreement form.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#identityForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<style>
	.agreement {
		padding: 1rem 2rem;
		font-family: serif;
		font-size: 1.2rem;
		line-height: 1.5;
		border-radius: 5px;
		border: 1px solid #999;
		/* box-shadow: 1px 1px 1px #999; */
		resize: none;
		overflow-y: scroll;
		outline: none;
		flex: 1;
		/* white-space: pre-line; */
	}
	.agreement h1, .agreement h2 {
		text-align: center;
	}
	.agr-footer {
		font-size: 1rem;
	}
	.agr-footer i {
		font-size: 1.5rem;
		position: relative;
		top: 0.2rem;
		margin-right: 0.5rem;
		margin-left: 0.1rem;
		color: green;
	}
</style>

<form class="pkp_form" id="agreementForm" method="post" action="{url op="saveAgreement"}" enctype="multipart/form-data">
	{* Help Link *}
	{help file="user-profile" class="pkp_help_tab"}

	{csrf}

	{fbvFormArea}
		{fbvFormSection title="dogovir.title"}
		<p>
			{translate key="dogovir.description"}
		</p>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea}
		{fbvFormSection class="agreement"}
			{$agreementText}
		{/fbvFormSection}
	{/fbvFormArea}


	<p class="agr-footer">
<i class="fa fa-check-square-o" aria-hidden="true"></i>
{translate key="dogovir.signed"} {$date|date_format:$dateFormatShort}
{* <br>{$date|date_format:$dateFormatLong} *}
	</p>
{* {$dateFormat}<br>
{$dateFormatLong}<br>
{setlocale(LC_ALL, 0)}<br>
{setlocale(LC_TIME, 0)}
{$testDate} *}
{* {date_create("2022-10-12")->getTimestamp()} *}
{* {date("d M Y",date_create("2022-10-12")->getTimestamp())} *}
	{* {fbvFormSection title="dogovir.title"}
		<p>{translate key="dogovir.description"}</p>
		<div class="agreement">
			{$agreementText}
		</div>
	{/fbvFormSection} *}

	{* <section>
		<p>Accession Agreement text:</p>
		<div>
			{$agreementText}
		</div>
	</section> *}

	{* {fbvFormButtons hideCancel=true submitText="common.close"} *}
</form>
<style>
	h1,
	h2 {
		all: revert;
		line-height: 2.143rem;
	}
	.agreement,
	.agreement * {
		font-size: 1.25rem !important;
		line-height: 1.5;
		text-align: justify;
	}

	.agreement h1,
	.agreement h1 * {
		font-size: 1.5rem !important;
	}

	.agreement h2,
	.agreement h2 * {
		font-size: 1.4rem;
	}
	.agreement .MsoFootnoteText,
	.agreement .MsoFootnoteText * {
		font-size: 1rem!important;
		line-height: 1.25;
	}
</style>
