<?php namespace Dotink\InkwellDocs
{
	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;
	use MarkdownExtraExtended;
	use iMarc\Pluck;

	class DocsController extends Inkwell\Controller
	{
		const DEFAULT_DOC_ROOT = 'external/docs';

		/**
		 *
		 */
		static public $docRoot = NULL;

		/**
		 *
		 */
		static public function __init($app, $config = array())
		{
			$doc_root = isset($config['doc_root'])
				? self::DEFAULT_DOC_ROOT
				: $config['doc_root'];

			self::$docRoot = $app->getRoot(NULL, $doc_root);
		}


		/**
		 *
		 */
		static private function generateToc($html)
		{
			$doc = new Pluck\Document($html);
			$toc = '';

			foreach ($doc->find('h2, h3, h4, h5, h6') as $item) {
				for ($level = (int) $item->nodeName[1]; $level > 2; $level--) {
					$toc .= '    ';
				}

				$toc .= sprintf('- [%s](%s)', $item->text(), '#' . $item->attr('id')) . PHP_EOL;
			}

			$parser = new MarkdownExtraExtended\Parser();

			return str_replace('<br />', '', $parser->transform($toc));
		}


		public function missing()
		{
			return $this['response'](HTTP\NOT_FOUND, Inkwell\View::create('html', [
				'staple' => 'Dotink/InkwellDocs/404.html'
			], [
				'id' => 'not_found'
			]));
		}


		/**
		 *
		 */
		public function show($path = '/')
		{
			$path = $this['request']->getURL()->getPath();

			if ($path[strlen($path) - 1] == '/') {
				$path .= 'index';
			}

			$path   .= '.md';
			$parser  = new MarkdownExtraExtended\Parser();

			if (!($contents = @file_get_contents(self::$docRoot . $path))) {
				$this->triggerError(HTTP\NOT_FOUND);
			}

			$doc = $parser->transform($contents);
			$toc = array();

			if ($path == '/index.md') {
				$id    = 'homepage';
				$title = 'A PHP Framework for PHP Developers :: inKWell PHP MVC';
			} else {
				$id    = 'doc';
				$toc   = self::generateToc($doc);
				$title = ucwords(str_replace('_', ' ', pathinfo($path, PATHINFO_FILENAME)));
			}

			return Inkwell\View::create('html', [
				'header' => 'Dotink/InkwellDocs/header.html',
				'staple' => 'Dotink/InkwellDocs/' . $id . '.html',
				'footer' => 'Dotink/InkwellDocs/footer.html'
			], [
				'id'    => $id,
				'doc'   => $doc,
				'toc'   => $toc,
				'title' => $title
			]);
		}
	}
}