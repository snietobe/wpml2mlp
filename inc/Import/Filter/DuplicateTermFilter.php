<?php # -*- coding: utf-8 -*-

namespace W2M\Import\Filter;

use stdClass;
use
	W2M\Import\Data,
	W2M\Import\Type,
	WP_Comment;
use WP_Term;

class DuplicateTermFilter implements TermImportFilterInterface {

	/**
	 * Checks if a term should be imported or not
	 *
	 * @param Type\ImportTermInterface $import_term
	 *
	 * @return bool
	 */
	public function term_to_import( Type\ImportTermInterface $import_term ) {

		$existing_term = get_term_by(
			'slug',
			$import_term->slug(),
			$import_term->taxonomy()
		);

		return FALSE === $existing_term;
	}

}