<?php
/**
 * @file SubmissionLanguagePlugin.inc.php
 * 
 * @class SubmissionLanguagePlugin
 * @brief Plugin class for the SubmissionLanguage plugin.
*/

import('lib.pkp.classes.plugins.HookRegistry');
import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.db.DAORegistry');
import('lib.pkp.classes.security.Role');
class SubmissionLanguagePlugin extends GenericPlugin {

	/**
	 * @copydoc GenericPlugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('TemplateManager::display', [$this, 'addLanguageInformation']);
		}
		return $success;
	}

	/**
	 * Provide a name for this plugin
	 *
	 * The name will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return __('plugins.generic.submissionLanguage.displayName');
	}

	public function addLanguageInformation($hookName, $args) {
		$template = $args[1];

		if ($template !== 'dashboard/index.tpl') {
			return false;
		}

		$request = Application::get()->getRequest();
		$user_roles = $request->getUser()->getRoles($request->getContext()->getId());

		// Since this plugin exposes IDs of all submissions in the current journal,
		// it should be available only to users with manager roles.
		if (!$this->isManager($user_roles)) {
			return false;
		}

		$templateMgr = TemplateManager::getManager($request);
		$script_path = __DIR__ . '/js/language.js';
		$css_url = __DIR__ . '/css/styles.css';

		$submissions = json_encode(array_values($this->fetchSubmissions()));
		$use_country_flags_bool = $this->getSetting($request->getContext()->getId(), 'useCountryFlags');

		if ($use_country_flags_bool) {
			$use_country_flags = 'true';
		} else {
			$use_country_flags = 'false';
		}

		// Prepend [submissionId,locale] pairs and plugin settings to the script,
		// then add it to the website.
		$script = "\nlet submissionLocales = JSON.parse('$submissions');\n\nlet useCountryFlags = $use_country_flags;\n\n" . file_get_contents($script_path);
		$templateMgr->addJavaScript('languages', $script, [ 'contexts' => 'backend', 'inline' => true ]);
		$templateMgr->addStyleSheet('plugin', $css_url, [ 'contexts' => 'backend' ]);
	}

	/**
	 * Provide a description for this plugin
	 *
	 * The description will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDescription() {
		return __('plugins.generic.submissionLanguage.description');
	}

	/**
	 * Add a settings action to the plugin's entry in the
	 * plugins list.
	 *
	 * @param Request $request
	 * @param array $actionArgs
	 * @return array
	 */
	public function getActions($request, $actionArgs) {

		// Get the existing actions
		$actions = parent::getActions($request, $actionArgs);

		// Only add the settings action when the plugin is enabled
		if (!$this->getEnabled()) {
			return $actions;
		}

		// Create a LinkAction that will make a request to the
		// plugin's `manage` method with the `settings` verb.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					[
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					]
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);

		// Add the LinkAction to the existing actions.
		// Make it the first action to be consistent with
		// other plugins.
		array_unshift($actions, $linkAction);

		return $actions;
	}

	/**
	 * Show and save the settings form when the settings action
	 * is clicked.
	 *
	 * @param array $args
	 * @param Request $request
	 * @return JSONMessage
	 */
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':

				// Load the custom form
				$this->import('SubmissionLanguageSettingsForm');
				$form = new SubmissionLanguageSettingsForm($this);

				// Fetch the form the first time it loads, before
				// the user has tried to save it
				if (!$request->getUserVar('save')) {
					$form->initData();
					return new JSONMessage(true, $form->fetch($request));
				}

				// Validate and save the form data
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					return new JSONMessage(true);
				}
		}
		return parent::manage($args, $request);
	}

	// Fetch all submissions in the current journal
	function fetchSubmissions() {
		$submissionDAO = DAORegistry::getDAO("SubmissionDAO");

		$context = Application::get()->getRequest()->getContext();
		$journal_id = $context->getId();
			$result = $submissionDAO->retrieve(
				'SELECT submission_id as submissionId,locale FROM submissions WHERE context_id = ?',
				[$journal_id]
			);

			$submissions = [];

			foreach ($result as $val) {
				$submissions[] = $val;
			}

			// Convert and return the submissions as
			// an array of [submission_id,locale] pairs.
			$submissions = array_map(function($pub_id_locale) {
				return array_values(get_object_vars($pub_id_locale));
			}, $submissions);

			return $submissions;
	}

	// Checks if currently loaded user has
	// manager role for loaded journal.
	function isManager($user_roles) {
		$manager_role = array_filter($user_roles, function($role_obj) {
			if ($role_obj->getId() == 16) {
				return true;
			}
			return false;
		});

		if (!empty($manager_role)) {
			return true;
		}

		return false;
	}
}
