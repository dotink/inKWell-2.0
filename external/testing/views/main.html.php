<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	%>
	<!doctype html>
	<html>
		<head>
			<title><%= $this->head->join('title', '::', TRUE) %></title>

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

			<% $this->head->place('common') %>

		</head>
		<body id="<%= $this['id'] ?: 'page' %>">

			<% $this->place('header') %>
			<% $this->place('staple') %>
			<% $this->place('footer') %>

		</body>
	</html>
	<%
}
