<?php
abstract class FeedItem_Common extends FeedItem {
	protected $elem;
	protected $xpath;
	protected $doc;

	function __construct($elem, $doc, $xpath) {
		$this->elem = $elem;
		$this->xpath = $xpath;
		$this->doc = $doc;

		try {

			$source = $elem->getElementsByTagName("source")->item(0);

			// we don't need <source> element
			if ($source)
				$elem->removeChild($source);
		} catch (DOMException $e) {
			//
		}
	}

	function get_element() {
		return $this->elem;
	}

	function get_author() {
		$author = $this->elem->getElementsByTagName("author")->item(0);

		if ($author) {
			$name = $author->getElementsByTagName("name")->item(0);

			if ($name) return $name->nodeValue;

			$email = $author->getElementsByTagName("email")->item(0);

			if ($email) return $email->nodeValue;

			if ($author->nodeValue)
				return $author->nodeValue;
		}

		$author = $this->xpath->query("dc:creator", $this->elem)->item(0);

		if ($author) {
			return $author->nodeValue;
		}
	}

	function get_comments_url() {
		//RSS only. Use a query here to avoid namespace clashes (e.g. with slash).
		//might give a wrong result if a default namespace was declared (possible with XPath 2.0)
		$com_url = $this->xpath->query("comments", $this->elem)->item(0);

		if($com_url)
			return $com_url->nodeValue;

		//Atom Threading Extension (RFC 4685) stuff. Could be used in RSS feeds, so it's in common.
		//'text/html' for type is too restrictive?
		$com_url = $this->xpath->query("atom:link[@rel='replies' and contains(@type,'text/html')]/@href", $this->elem)->item(0);

		if($com_url)
			return $com_url->nodeValue;
	}

	function get_comments_count() {
		//also query for ATE stuff here
		$query = "slash:comments|thread:total|atom:link[@rel='replies']/@thread:count";
		$comments = $this->xpath->query($query, $this->elem)->item(0);

		if ($comments) {
			return $comments->nodeValue;
		}
	}

	// this is common for both Atom and RSS types and deals with various media: elements
	function get_enclosures() {
		$encs = [];

		$enclosures = $this->xpath->query("media:content", $this->elem);

		foreach ($enclosures as $enclosure) {
			$enc = new FeedEnclosure();

			$enc->type = $enclosure->getAttribute("type");
			$enc->link = $enclosure->getAttribute("url");
			$enc->length = $enclosure->getAttribute("length");
			$enc->height = $enclosure->getAttribute("height");
			$enc->width = $enclosure->getAttribute("width");

			$medium = $enclosure->getAttribute("medium");
			if (!$enc->type && $medium) {
				$enc->type = strtolower("$medium/generic");
			}

			$desc = $this->xpath->query("media:description", $enclosure)->item(0);
			if ($desc) $enc->title = strip_tags($desc->nodeValue);

			array_push($encs, $enc);
		}

		$enclosures = $this->xpath->query("media:group", $this->elem);

		foreach ($enclosures as $enclosure) {
			$enc = new FeedEnclosure();

			$content = $this->xpath->query("media:content", $enclosure)->item(0);

			if ($content) {
				$enc->type = $content->getAttribute("type");
				$enc->link = $content->getAttribute("url");
				$enc->length = $content->getAttribute("length");
				$enc->height = $content->getAttribute("height");
				$enc->width = $content->getAttribute("width");

				$medium = $content->getAttribute("medium");
				if (!$enc->type && $medium) {
					$enc->type = strtolower("$medium/generic");
				}

				$desc = $this->xpath->query("media:description", $content)->item(0);
				if ($desc) {
					$enc->title = strip_tags($desc->nodeValue);
				} else {
					$desc = $this->xpath->query("media:description", $enclosure)->item(0);
					if ($desc) $enc->title = strip_tags($desc->nodeValue);
				}

				array_push($encs, $enc);
			}
		}

		$enclosures = $this->xpath->query("media:thumbnail", $this->elem);

		foreach ($enclosures as $enclosure) {
			$enc = new FeedEnclosure();

			$enc->type = "image/generic";
			$enc->link = $enclosure->getAttribute("url");
			$enc->height = $enclosure->getAttribute("height");
			$enc->width = $enclosure->getAttribute("width");

			array_push($encs, $enc);
		}

		return $encs;
	}

	function count_children($node) {
		return $node->getElementsByTagName("*")->length;
	}

	function subtree_or_text($node) {
		if ($this->count_children($node) == 0) {
			return $node->nodeValue;
		} else {
			return $node->c14n();
		}
	}

}
