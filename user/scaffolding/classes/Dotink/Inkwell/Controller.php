<?php namespace <%= $this->getNamespace() %>
{
	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;
	use Dotink\Inkwell\View;

	/**
	 * The <%= $this->getShortName() %> class.
	 *
	 * @copyright Copyright (c) <%= date('Y') %>, <%= $this->getScope('copyright') %>
	 * @author <%= $this->getScope('author') %> [<%= $this->getScope('tag') %>] <<%= $this->getScope('email') %>>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 */
	class <%= $this->getShortName() %> extends Inkwell\Controller
	{
		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration data for this class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			//
			// Initialization logic for the parent class
			//

			return TRUE;
		}

		<% foreach ($this->get('actions') as $action) { %>

		/**
		 * The entry point for the <%= $action %> action
		 *
		 * @access public
		 * @return Response The response object
		 */
		public function <%= $action %>()
		{

		}

		<% } %>

	}
}
