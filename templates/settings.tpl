{**
 * templates/settings.tpl
 *
 * Settings form for the submissionLanguage plugin.
 *}
<script>
	$(function() {ldelim}
		$('#submissionLanguageSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
	class="pkp_form"
	id="submissionLanguageSettings"
	method="POST"
	action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
	<!-- Always add the csrf token to secure your form -->
	{csrf}

	{fbvFormArea id="submissionLanguageSettingsArea"}
		{fbvFormSection for="useCountryFlags" list=true}
			{fbvElement
				type="checkbox"
				id="useCountryFlags"
				value="1"
				checked=$useCountryFlags
				label="plugins.generic.submissionLanguage.useCountryFlags.description"
			}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
