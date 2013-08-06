<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	%>

	<div role="main" style="padding: 5%;">
		<h1>Tests</h1>
		<p>
			Here's a list of the tests we're going to run, when ready, press "Go!"
		</p>
		<ul>
			<% $this->each('tests', function($status, $name) { %>
				<li>
					<%= $name %>
					<% if (is_bool($status)) { %>
						<%= $status
							? '... <span style="color: green;">OK</span>'
							: '... <span style="color: red;">FAILED</span>'
						%>
					<% } %>
				</li>
			<% }); %>
		</ul>
		<form method="post" action="">
			<button class="suggested">Go!</button>
		</form>
	</div>

	<%
}
