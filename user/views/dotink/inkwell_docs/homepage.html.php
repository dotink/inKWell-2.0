<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	 $this->head->asset('common', 'styles/dotink/inkwell_docs/main.css');

	%>
	<header>
		<a href="/">
			<img class="logo" src="http://inkwell.dotink.org/assets/images/inkwell_logo_dark.png" />
		</a>
		<h1>A PHP Framework for PHP Developers</h1>
	</header>
	<h2>
		 We've reached 2.0 Beta! and we couldn't have done it without you, the PHP community.
	</h2>

	<div class="news">
		<p>
			The release of inKWell 2.0 Beta is bringing with it a lot of changes, including changes to this site.  Since we're still in the process of developing all the great new documentation for inKWell 2.0, we wanted to welcome feedback as early as possible in the process.  Documentation for 1.0 is <a href="/downloads/inkwell-1.0.docs.tar.gz">available for download here</a>.
		</p>
		<p>
			As we begin rolling out the new documentation, please refrain from posting comments related to separate topics on the currently available pages.  If you have feedback for a particular planned section, you can create an issue on <a href="http://www.github.com/dotink/inkwell-2.0">the github project</a>.
		</p>
		<p>
			As always, if you have any questions about the current or previous version of inKWell you can contact us directly at <a href="mailto:info@dotink.org">info@dotink.org</a>.
		</p>
	</div>

	<div class="brochure group">
		<div>
			<section>
				<h3>Fast</h3>
				<img src="/images/fast.png" width="128" />
				<p>
					This page, from start to finish in &lt; 40ms on a Lenovo U310.
				</p>
			</section>
		</div>
		<div>
			<section>
				<h3>Fun</h3>
				<img src="/images/fun.png" width="128" />
				<p>
					Unique architecture and design gives tons of room to customize.
				</p>
			</section>
		</div>
		<div>
			<section>
				<h3>Flexible</h3>
				<img src="/images/flexible.png" width="128" />
				<p>
					Convention when you want, configuration when you need.
				</p>
			</section>
		</div>
	</div>

	<div class="group">
		<%= $this['doc'] %>
	</div>

	<%
}
