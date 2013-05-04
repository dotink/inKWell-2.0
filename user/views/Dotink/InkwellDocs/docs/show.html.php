<% namespace Dotink\Inkwell\View\HTML {

	/**
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 */

	$this->head->asset('highlight', 'http://yandex.st/highlightjs/7.3/styles/tomorrow-night-bright.min.css');
	$this->head->asset('highlight', 'http://yandex.st/highlightjs/7.3/highlight.min.js');
	$this->head->asset('toc',       'scripts/dotink/toc_sex.js');

	$this->head->push('title', $this['title']);

	$this->head->add('common', 'Dotink/InkwellDocs/docs/head.html');

	%>
	<script type="text/javascript">

		hljs.initHighlightingOnLoad();

		$(function(){
			TOCSex.init('.toc', '.principal');
		});

	</script>

	<h1><%= $this['title'] %></h1>
	<div class="group">
		<div class="preface toc">
			<%= $this['toc'] %>
		</div>
		<div class="principal">

			<%= $this['doc'] %>

			<div id="disqus_thread"></div>
			<script type="text/javascript">
				/* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
				var disqus_shortname = 'inkwellphpmvc'; // required: replace example with your forum shortname

				/* * * DON'T EDIT BELOW THIS LINE * * */
				(function() {
					var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
					dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
					(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
				})();
			</script>
			<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
			<a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
		</div>
	</div>
	<%
}
