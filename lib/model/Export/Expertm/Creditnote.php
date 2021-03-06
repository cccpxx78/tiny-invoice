<?php
/**
 * Export_Expertm_User class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

use \Skeleton\Database\Database;

class Export_Expertm_Creditnote extends Export_Expertm {

	/**
	 * Run
	 *
	 * @access public
	 */
	public function run() {
		$months = $this->get_data();

		$db = Database::get();
		$ids = [];
		foreach ($months as $month) {
			$ids = array_merge($ids, $db->get_column('SELECT id FROM creditnote WHERE created LIKE "' . $month . '%"', [ ]));
		}

		$creditnotes = [];
		foreach ($ids as $id) {
			$creditnotes[$id] = Creditnote::get_by_id($id);
		}
		$output1 = '';
		$output2 = '';

		foreach ($creditnotes as $creditnote) {
			$output1 .= $this->num(9, 4000000);
			$output1 .= $this->num(9, 2);
			$output1 .= $this->num(9, $creditnote->customer_contact->customer_contact_export_id);
			$output1 .= $this->alf(3, 'EUR');
			$output1 .= $this->alf(1, 'C');
			$output1 .= $this->num(9, $creditnote->number);
			$output1 .= $this->num(8, date('dmY', strtotime($creditnote->created)));
			$output1 .= $this->num(8, date('dmY', strtotime($creditnote->created)));
			$output1 .= $this->cur(12, 1);
			$output1 .= $this->num(1, 0);
			$output1 .= $this->num(1, 1);
			$output1 .= $this->alf(20, '');
			$output1 .= $this->alf(20, '');
			$output1 .= $this->cur(20, $creditnote->get_price_incl());
			$output1 .= $this->cur(20, $creditnote->get_price_incl());
			$output1 .= $this->cur(20, 0);
			$output1 .= $this->num(2, date('n', strtotime($creditnote->created)));
			$output1 .= $this->num(6, date('Ym', strtotime($creditnote->created)));
			$output1 .= $this->num(1, 0);
			$output1 .= "\r\n";

			$vat = 0;
			foreach ($creditnote->get_vat_array() as $price) {
				$vat += $price;
			}

			$i = 1;
			foreach ($creditnote->get_creditnote_items() as $creditnote_item) {
				$output2 .= $this->num(9, $creditnote_item->product_type->identifier);
				$output2 .= $this->alf(1, 'C');
				$output2 .= $this->num(9, $creditnote->number);
				$output2 .= $this->alf(50, '');
				$output2 .= $this->cur(20, $creditnote_item->get_price_excl());
				$output2 .= $this->cur(20, $creditnote_item->get_price_excl());
				if ($creditnote_item->get_price_excl() > 0) {
					$output2 .= $this->alf(1, 'D');
				} else {
					$output2 .= $this->alf(1, 'C');
				}
				if ($vat > 0) {
					$output2 .= $this->num(3, 5);
				} else {
					$output2 .= $this->num(3, 57);
				}
				$output2 .= $this->num(9, $i);
				$output2 .= "\r\n";
				$i++;
			}
			foreach ($creditnote->get_vat_array() as $vat) {
				$output2 .= $this->num(9, 0);
				$output2 .= $this->alf(1, 'C');
				$output2 .= $this->num(9, $creditnote->number);
				$output2 .= $this->alf(50, '');
				$output2 .= $this->cur(20, $vat);
				$output2 .= $this->cur(20, $vat);
				$output2 .= $this->alf(1, 'D');
				$output2 .= $this->num(3, 11);
				$output2 .= $this->num(9, $i);
				$output2 .= "\r\n";
				$i++;
			}

		}
		$file = \Skeleton\File\File::store('expertm_credit_notes_' . date('Ymd') . '_1.txt', $output1);
 		$this->file_id = $file->id;
		$this->save();

		$file = \Skeleton\File\File::store('expertm_credit_notes_' . date('Ymd') . '_2.txt', $output2);
		$export = new self();
		$export->file_id = $file->id;
		$export->save();
	}

	/**
	 * Generate alfa
	 *
	 * @access private
	 * @param string $name
	 * @return string $alfa
	 */
	private function generate_alfa($name) {
		$alfa = $name;
		$alfa = str_replace(' ', '', $alfa);
		$alfa = str_replace('.', '', $alfa);
		$alfa = str_replace('@', '', $alfa);
		$alfa = str_replace('&', '', $alfa);
		$alfa = str_replace('-', '', $alfa);
		$alfa = str_replace('_', '', $alfa);
		$alfa = str_replace('!', '', $alfa);
		$alfa = str_replace(',', '', $alfa);
		$alfa = str_replace('?', '', $alfa);
		$alfa = str_replace("\"", '', $alfa);
		$alfa = str_replace(';', '', $alfa);
		$alfa = str_replace('*', '', $alfa);
		$alfa = str_replace('\'', '', $alfa);
		$alfa = strtoupper($alfa);
		$alfa = trim($alfa);
		return $alfa;
	}

	/**
	 * Export VAT
	 * Export the VAT string to the ExpertM format
	 *
	 * @access private
	 * @return string $vat
	 */
	private function export_vat($vat, Country $country) {
		if ($vat == '') {
			return $vat;
		}
		if ($country->iso2 == 'BE') {
			return substr($vat, 1, 3) . '.' . substr($vat, 4, 3) . '.' . substr($vat, 7);
		} else {
			return $vat;
		}
	}

}
