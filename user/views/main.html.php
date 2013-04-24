<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	$this->head->asset('common', 'http://dotink.github.io/inKLing/inkling.css');

	%>
	<!doctype html>
	<html>
		<head>
			<title><%= $this->head->join('title', '::', TRUE) %></title>

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

			<% $this->head->place('common') %>

		</head>
		<body>

			<% $this->place('header') %>
			<% $this->place('staple') %>
			<% $this->place('footer') %>

		</body>
	</html>
	<%
}
