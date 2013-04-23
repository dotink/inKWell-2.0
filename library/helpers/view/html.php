<?php namespace Dotink\Inkwell\View\HTML {

	function e($value)
	{
		return htmlentities($value, ENT_QUOTES, 'UTF-8');
	}

	function option($value, $name, $check_value = NULL)
	{
		$template = ($check_value !== NULL && $check_value == $value)
			? '<option selected="selected" value="%s">%s</option>'
			: '<option value="%s">%s</option>';

		return sprintf($template, e($value), $name);
	}

}