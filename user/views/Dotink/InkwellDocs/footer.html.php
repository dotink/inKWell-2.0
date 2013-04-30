<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	 %>
	 	inKWell consumed <%= number_format(memory_get_usage(TRUE) / 1024 / 1024, 2) %>MB of memory while generating this page.
	 <%
}