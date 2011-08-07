<?php

$wgExtensionFunctions[] = 'wfReplaceOnSave';

/* register parser hook */
$wgExtensionCredits['parserhook'][] = array(
    'name' => 'ReplaceOnSave',
    'author' => 'Lance Gatlin',
    'version' => '0.1',
);
/*function dout($t)
{
	$f = fopen('extensions/dout.txt', 'a+t');
	if($f === false)
		return;
	
	fwrite($f, $t);
	fclose($f);
}*/
// Sets up <pre> and <nowiki> sections to be ignored, so that ReplaceOnSave tags within <pre> or <nowiki> can still be displayed for docs etc.
$gReplaceOnSaveHook['nowiki'] = 'ReplaceOnSave_doNothing';
$gReplaceOnSaveHook['pre'] = 'ReplaceOnSave_doNothing';
$gBeforeReplaceOnSaveHook = array();
$gAfterReplaceOnSaveHook = array();

function wfReplaceOnSave() {
    global $wgParser, $wgHooks;

   $wgHooks['ArticleSave'][] = 'ReplaceOnSave_ArticleSave';
}

function ReplaceOnSave_doReplace(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor)
{
	global $gReplaceOnSaveHook;
	
	// Parse the text
	// Replaces all <secure></secure> in text with a unique id (and html comments) and returns the new text
	// Returns an array that contains information about the <secure> tags located
	$ptext = Parser::extractTagsAndParams(
					   array_keys($gReplaceOnSaveHook),
					   $text,
					   $matches );	
	
	foreach($matches as $k => $i)
	{
		$tag = $i[0];
		$fulltag = $i[3];
		$content = $i[1];
		$params = $i[2];
		// If a handler for the tag exists
		if (isset($gReplaceOnSaveHook[$tag]))
		{
/*			// Recurse content for ReplaceOnSaveTags
			if(strpos($content, '<') !== false)
			{
				$temp = $content;
				ReplaceOnSave_ArticleSave($article, $user, $temp, $summary, $minoredit, $watchthis, $sectionanchor);
				if($temp != $content)
				{
					$fulltag = str_replace($content, $temp, $fulltag);
					$content = $temp;
				}
			}*/
			
			$subst_text = call_user_func($gReplaceOnSaveHook[$tag], $tag, $content, $params, $fulltag, $article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor);
			if($subst_text === false)
				// Replace with no changes
				$subst_text = $fulltag;
				// Replacement text, so set changed flag
			else $changed = true;
		}
		// This match is an html comment just replace with no changes
		else $subst_text = $fulltag;
		
		// Save work for later in case there are no changes
		$matches[$k] = $subst_text;
	}
	
	// If any changes were made to the text then "save" the changes
	if($changed == true)
	{
		foreach($matches as $k => $subst_text)
			// Replace the unique tag in ptext with the final version of the text (may remain the same)
			$ptext = str_replace($k, $subst_text, $ptext);
			
		$text = $ptext;
/*		// Recurse until no more changes
		if(strpos($text, '<') !== false)
			ReplaceOnSave_ArticleSave($article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor);*/

	}
}

function ReplaceOnSave_ArticleSave(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor) 
{
	global $gReplaceOnSaveHook, $gBeforeReplaceOnSaveHook, $gAfterReplaceOnSaveHook;
	
	foreach($gBeforeReplaceOnSaveHook as $f)
	{
		$result = call_user_func($f, $article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor);
		if($result !== false)
			$text = $result;
	}
		
	ReplaceOnSave_doReplace($article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor);
	
	foreach($gAfterReplaceOnSaveHook as $f)
	{
		$result = call_user_func($f, $article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor);
		if($result !== false)
			$text = $result;
	}
	
	return true;
}

function ReplaceOnSave_doNothing($tag, $content, $params, $fulltag, $article, $user, $text, $summary, $minoredit, $watchthis, $sectionanchor)
{
	return false;
}

?>