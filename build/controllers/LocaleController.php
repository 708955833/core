<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

use yii\console\Controller;
use yii\helpers\FileHelper;

/**
 * http://www.unicode.org/cldr/charts/supplemental/language_plural_rules.html
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class LocaleController extends Controller
{
	public $defaultAction = 'plural';

	/**
	 * Generates the plural rules data.
	 *
	 * This command will parse the plural rule XML file from CLDR and convert them
	 * into appropriate PHP representation to support Yii message translation feature.
	 * @param string $xmlFile the original plural rule XML file (from CLDR). This file may be found in
	 *      http://www.unicode.org/Public/cldr/latest/core.zip
	 * Extract the zip file and locate the file "common/supplemental/plurals.xml".
	 * @throws Exception
	 */
	public function actionPlural($xmlFile)
	{
		if (!is_file($xmlFile)) {
			throw new Exception("The source plural rule file does not exist: $xmlFile");
		}

		$xml = simplexml_load_file($xmlFile);

		$allRules = array();

		$patterns = array(
			'/n in 0..1/' => '(n==0||n==1)',
			'/\s+is\s+not\s+/i' => '!=', //is not
			'/\s+is\s+/i' => '==', //is
			'/n\s+mod\s+(\d+)/i' => 'fmod(n,$1)', //mod (CLDR's "mod" is "fmod()", not "%")
			'/^(.*?)\s+not\s+in\s+(\d+)\.\.(\d+)/i' => '!in_array($1,range($2,$3))', //not in
			'/^(.*?)\s+in\s+(\d+)\.\.(\d+)/i' => 'in_array($1,range($2,$3))', //in
			'/^(.*?)\s+not\s+within\s+(\d+)\.\.(\d+)/i' => '($1<$2||$1>$3)', //not within
			'/^(.*?)\s+within\s+(\d+)\.\.(\d+)/i' => '($1>=$2&&$1<=$3)', //within
		);
		foreach ($xml->plurals->pluralRules as $node) {
			$attributes = $node->attributes();
			$locales = explode(' ', $attributes['locales']);
			$rules = array();

			if (!empty($node->pluralRule)) {
				foreach ($node->pluralRule as $rule) {
					$expr_or = preg_split('/\s+or\s+/i', $rule);
					foreach ($expr_or as $key_or => $val_or) {
						$expr_and = preg_split('/\s+and\s+/i', $val_or);
						$expr_and = preg_replace(array_keys($patterns), array_values($patterns), $expr_and);
						$expr_or[$key_or] = implode('&&', $expr_and);
					}
					$expr = preg_replace('/\\bn\\b/', '$n', implode('||', $expr_or));
					$rules[] = preg_replace_callback('/range\((\d+),(\d+)\)/', function ($matches) {
						if ($matches[2] - $matches[1] <= 5) {
							return 'array(' . implode(',', range($matches[1], $matches[2])) . ')';
						} else {
							return $matches[0];
						}
					}, $expr);

				}
				foreach ($locales as $locale) {
					$allRules[$locale] = $rules;
				}
			}
		}
		// hard fix for "br": the rule is too complex
		$allRules['br'] = array(
			0 => 'fmod($n,10)==1&&!in_array(fmod($n,100),array(11,71,91))',
			1 => 'fmod($n,10)==2&&!in_array(fmod($n,100),array(12,72,92))',
			2 => 'in_array(fmod($n,10),array(3,4,9))&&!in_array(fmod($n,100),array_merge(range(10,19),range(70,79),range(90,99)))',
			3 => 'fmod($n,1000000)==0&&$n!=0',
		);
		if (preg_match('/\d+/', $xml->version['number'], $matches)) {
			$revision = $matches[0];
		} else {
			$revision = -1;
		}

		echo "<?php\n";
		echo <<<EOD
/**
 * Plural rules.
 *
 * This file is automatically generated by the "yiic locale/plural" command under the "build" folder.
 * Do not modify it directly.
 *
 * The original plural rule data used for generating this file has the following copyright terms:
 *
 * Copyright © 1991-2007 Unicode, Inc. All rights reserved.
 * Distributed under the Terms of Use in http://www.unicode.org/copyright.html.
 *
 * @revision $revision (of the original plural file)
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
EOD;
		echo "\nreturn " . var_export($allRules, true) . ';';
	}
}
