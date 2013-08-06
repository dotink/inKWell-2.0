<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	%>

	<h1>
		<%= $this['file']->getName() %>
	</h1>
	<pre><%= e($this['file']->read()) %></pre>

	<%
}