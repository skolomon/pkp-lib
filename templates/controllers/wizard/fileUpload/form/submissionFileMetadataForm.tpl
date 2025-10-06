{**
 * templates/controllers/wizard/fileUpload/form/submissionFileMetadataForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * File metadata form.
 *
 * Parameters:
 *  $submissionFile: The submission file.
 *  $stageId: The workflow stage id from which the upload
 *   wizard was called.
 *  $showButtons: True iff form buttons should be presented.
 *}
{assign var=metadataFormId value="metadataForm"|uniqid}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$metadataFormId}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');

		//Sasz AI:
		// Disable checkbox and submit button when the form is submitted,
		// and ensure the checkbox value is preserved via a hidden input.
		(function() {ldelim}
			var form = $('#{$metadataFormId}');
			var checkboxSelector = '#skipAI'; // change to your checkbox id
			var spinnerSelector = '#wizardButtons>.pkp_spinner';
			var cancenButton = '#cancelButton';
			var glowCard = '.glow-card';

			form.on('submit', function(e) {ldelim}
				var $cb = form.find(checkboxSelector);

				// If checkbox exists, copy its state to a hidden input so the value is sent even when disabled
				if ($cb.length) {ldelim}
					var name = $cb.attr('name') || 'skipAI';
					var checkedVal = $cb.is(':checked') ? '1' : '0';

					// remove any previous hidden sentinel we added
					form.find('input[name="' + name + '"]._preserve_disabled').remove();

					// add hidden input with same name/value so server receives it when checkbox is disabled
					$('<input>')
					.attr({ type: 'hidden', name: name, value: checkedVal })
					.addClass('_preserve_disabled')
					.appendTo(form);

					// disable the checkbox to prevent user interaction during submit
					$cb.prop('disabled', true);

					// make the label text gray by adding a class to the label that wraps or references the checkbox
					// try to find <label for="myCheckbox"> first, fallback to parent label
					var $label = form.find('label[for="' + $cb.attr('id') + '"]');
					if (!$label.length) $label = $cb.closest('label');
					$label.addClass('label-disabled');

					var $cancelBtn = $(cancenButton).first();
					if ($cancelBtn.length) {ldelim}
						$cancelBtn.css('display', 'none');
					{rdelim}

					if(checkedVal == '0') {ldelim}
						var $glowCrd = form.find(glowCard);
						if ($glowCrd.length) {ldelim}
							$glowCrd.addClass('glow-card-animate');
						{rdelim}

						// spinner handling: reveal existing spinner or insert one
						var $spinner = $(spinnerSelector).first();
						// console.log("spinner: ", $spinner);
						if ($spinner.length) {ldelim}
							// remove inline display:none if present
							$spinner.css('display', '');
						{rdelim}
					{rdelim}
				{rdelim}
		
				// let the submission continue
			{rdelim});
		{rdelim})();
	{rdelim});
</script>

<form class="pkp_form" id="{$metadataFormId}" action="{url component="api.file.ManageFileApiHandler" op="saveMetadata" submissionId=$submissionFile->getData('submissionId') stageId=$stageId reviewRoundId=$reviewRoundId fileStage=$submissionFile->getData('fileStage') submissionFileId=$submissionFile->getId() escape=false}" method="post">
	{csrf}

	{* Editable metadata *}
	{fbvFormArea id="fileMetaData"}

		{* File name and detail summary *}
		{fbvFormSection for="name" size=$fbvStyles.size.LARGE title="submission.form.name" required=true}
			{fbvElement type="text" id="name" value=$submissionFile->getData('name') multilingual=true maxlength="255" required=true}
		{/fbvFormSection}

		{* Sasz AI: *}
		{if $aiMetadata && $genre && $genre->getId() == 1}
			<style>
				:root {
					--pad: 10px;
					--radius: 16px;
					--border-size: 6px;
				}

				/* container with gradient border background */
				.glow-card {
					max-width: 80ch;
					position: relative;
					padding: calc(var(--pad) + var(--border-size));
					border-radius: calc(var(--radius) + var(--border-size));
					background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
				}

				/* border gradient (static by default) */
				.glow-card::before {
					content: "";
					position: absolute;
					inset: 0;
					border-radius: calc(var(--radius) + var(--border-size));
					padding: var(--border-size);
					-webkit-mask:
						linear-gradient(#fff 0 0) content-box,
						linear-gradient(#fff 0 0);
					-webkit-mask-composite: xor;
					mask-composite: exclude;
					pointer-events: none;
					background:
						conic-gradient(from var(--angle, 0deg) at 50% 50%,
							#ff7a18, #af002d, #7a00ff, #00b7ff, #7aff6a, #ffd400, #ff7a18);
					filter: blur(6px) saturate(120%);
					z-index: 0;
				}

				/* CSS Houdini property for animatable angle */
				@property --angle {
					syntax: '<angle>';
					initial-value: 0deg;
					inherits: false;
				}

				/* inner wrapper */
				.glow-inner {
					position: relative;
					border-radius: var(--radius);
					background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
					padding: var(--pad);
					z-index: 1;
					overflow: hidden;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				/* gradient text (static by default) */
				.gradient-text {
					position: relative;
					font-weight: 700;
					font-size: clamp(22px, 6vw, 36px);
					line-height: 1;
					letter-spacing: 0.6px;
					background: linear-gradient(90deg,
							#ff7a18 0%,
							#ff2d95 20%,
							#7a00ff 40%,
							#00b7ff 60%,
							#7aff6a 80%,
							#ff7a18 100%);
					background-size: 200% 100%;
					-webkit-background-clip: text;
					background-clip: text;
					color: transparent;
					padding: 6px 12px;
				}

				/* shimmer overlay (static by default) */
				.gradient-text::after {
					content: "";
					position: absolute;
					inset: 0;
					pointer-events: none;
					background: linear-gradient(105deg,
							transparent 40%,
							rgba(255, 255, 255, 0.3) 50%,
							transparent 60%);
					mix-blend-mode: overlay;
					transform: translateX(-100%);
					border-radius: 6px;
				}

				/* --- Animations only when .glow-card-animate is present --- */
				.-card-animateglow {
					/* animation: pulse-glow 3s ease-in-out infinite; */
				}

				.glow-card-animate::before {
					animation: rotate-border 4s linear infinite;
				}

				.glow-card-animate .gradient-text {
					animation: gradient-shift 3s ease infinite;
				}

				.glow-card-animate .gradient-text::after {
					animation: shimmer 2s linear infinite;
				}

				/* keyframes */
				@keyframes rotate-border {
					to {
						--angle: 360deg;
					}
				}

				@keyframes gradient-shift {
					0%,
					100% {
						background-position: 0% 50%;
					}
					50% {
						background-position: 100% 50%;
					}
				}

				@keyframes shimmer {
					to {
						transform: translateX(200%);
					}
				}

				@keyframes pulse-glow {
					0%,
					100% {
						box-shadow:
							0 8px 30px rgba(255, 122, 24, 0.2),
							0 4px 15px rgba(175, 0, 45, 0.15),
							inset 0 1px 0 rgba(255, 255, 255, 0.02);
					}
					50% {
						box-shadow:
							0 12px 40px rgba(122, 0, 255, 0.3),
							0 6px 20px rgba(0, 183, 255, 0.2),
							inset 0 1px 0 rgba(255, 255, 255, 0.04);
					}
				}

				/* layout helpers */
				.wrap {
					position: relative;
					display: flex;
					gap: 18px;
					align-items: center;
					justify-content: center;
				}
				.sub {
					/* margin-top: var(--pad); */
					text-align: justify;
				}
				.pkp_ai_descr {
					display: inline-block;
					padding: 0.25rem 0;
					font-size: 1rem;
					font-weight: 700;
					text-decoration: none;
					border: none;
					box-shadow: none;
					color: #006798;
					animation: fade-pulse 2s ease-in-out infinite;
				}
				.label-disabled {
					color: #8a8f98; /* adjust as needed */
					opacity: 0.85;
				}
				.glow-inner .ai-skip-label {
					/* margin-top: 0.25rem; */
					font-weight: normal;
					font-size: inherit;
					display: flex;
					align-items: flex-start;
				}
			</style>
			<div class="glow-card" role="status" aria-live="polite" aria-label="AI processing">
				<div class="glow-inner">
					<div style="position:relative;">
						<div style="text-align:center" class="gradient-text">
							{{__('ai.processing')}}
						</div>
						<div class="sub pkp_ai_descr">
							{{__('ai.processing.description')}}
						</div>
						<label class="ai-skip-label">
							<input type="checkbox" id="skipAI" name="skipAI" class="pkpFormField--options__input"
								{if $data.skipAI}checked="checked" {/if} {if $data.disableSkipAI}disabled="disabled" {/if} />
							&nbsp;{{__('ai.processing.skip')}}
						</label>
					</div>
				</div>	
			</div>
		{/if}

		{* Supplementary file metadata *}
		{if $genre && $genre->getCategory() == $smarty.const.GENRE_CATEGORY_SUPPLEMENTARY}
			{fbvFormSection}
				{fbvElement label="common.description" type="textarea" id="description" value=$submissionFile->getData('description') multilingual=true}
				{fbvElement label="submission.supplementary.creator" inline=true size=$fbvStyles.size.MEDIUM type="text" id="creator" value=$submissionFile->getData('creator') multilingual=true maxlength="255"}
				{fbvElement label="submission.supplementary.publisher" inline=true size=$fbvStyles.size.MEDIUM type="text" id="publisher" value=$submissionFile->getData('publisher') multilingual=true maxlength="255"}
				{fbvElement label="common.source" inline=true size=$fbvStyles.size.MEDIUM type="text" id="source" value=$submissionFile->getData('source') multilingual=true maxlength="255"}
				{fbvElement label="submission.supplementary.subject" inline=true size=$fbvStyles.size.MEDIUM type="text" id="subject" value=$submissionFile->getData('subject') multilingual=true maxlength="255"}
				{fbvElement label="submission.supplementary.sponsor" inline=true size=$fbvStyles.size.MEDIUM type="text" id="sponsor" value=$submissionFile->getData('sponsor') multilingual=true maxlength="255"}
				{fbvElement label="common.date" inline=true size=$fbvStyles.size.SMALL type="text" id="dateCreated" value=$submissionFile->getData('dateCreated') class="datepicker"}
				{fbvElement label="common.language" inline=true size=$fbvStyles.size.SMALL type="text" id="language" value=$submissionFile->getData('language') maxlength="255"}
			{/fbvFormSection}
		{/if}

		{* Artwork metadata *}
		{if $genre && $genre->getCategory() == $smarty.const.GENRE_CATEGORY_ARTWORK}
			{fbvFormSection title="grid.artworkFile.caption" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="textarea" id="artworkCaption" height=$fbvStyles.height.SHORT value=$submissionFile->getData('caption')}
			{/fbvFormSection}
			{fbvFormSection title="grid.artworkFile.credit" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="textarea" id="artworkCredit" height=$fbvStyles.height.SHORT value=$submissionFile->getData('credit')}
			{/fbvFormSection}
			{fbvFormSection title="grid.artworkFile.copyrightOwner" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="textarea" id="artworkCopyrightOwner" height=$fbvStyles.height.SHORT value=$submissionFile->getData('copyrightOwner')}
			{/fbvFormSection}
			{fbvFormSection title="grid.artworkFile.permissionTerms" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="textarea" id="artworkPermissionTerms" height=$fbvStyles.height.SHORT value=$submissionFile->getData('terms')}
			{/fbvFormSection}
		{/if}

	{/fbvFormArea}

	{if $supportsDependentFiles}
		{capture assign=dependentFilesGridUrl}{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.files.dependent.DependentFilesGridHandler" op="fetchGrid" submissionId=$submissionFile->getData('submissionId') submissionFileId=$submissionFile->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}{/capture}
		{load_url_in_div id="dependentFilesGridDiv" url=$dependentFilesGridUrl}
	{/if}

	{if $showButtons}
		{fbvElement type="hidden" id="showButtons" value=$showButtons}
		{fbvFormButtons submitText="common.save"}
	{/if}
</form>
