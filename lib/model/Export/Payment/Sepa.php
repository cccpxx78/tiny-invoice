<?php
/**
 * Export_Payment_Sepa class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

class Export_Payment_Sepa extends Export {

	/**
	 * Run
	 *
	 * @access public
	 */
	public function run() {
		$data = $this->get_data();
		$document_ids = $data['document_ids'];
		$total_price = 0;

		$documents = [];
		foreach ($document_ids as $document_id) {
			$document = Document::get_by_id($document_id);
			$documents[] = $document;
			if ($document->classname != 'Document_Incoming_Invoice') {
				throw new Exception('Incorrect document type');
			}
			$total_price += $document->price_incl;
		}


		$organization = new \Tigron\Sepa\Organization();
		$organization->name = Setting::get_by_name('company')->value;
		/**
		 * Set the identification
		 *
		 * Possible types: bic, bei or kbo-bce
		 */
		$organization->set_identification('kbo-bce', Setting::get_by_name('vat')->value);

		$credit = new \Tigron\Sepa\File\Credit();
		$credit->messageIdentification = $this->id;
		$credit->initiatingParty = $organization;

		$debtor = new \Tigron\Sepa\Debtor();
		$debtor->name = Setting::get_by_name('company')->value;
		$debtor->country = Country::get_by_id(Setting::get_by_name('country_id')->value)->iso2;
		$debtor->zipcode = Setting::get_by_name('zipcode')->value;
		$debtor->city = Setting::get_by_name('city')->value;
		$debtor->street = Setting::get_by_name('street')->value;
		$debtor->housenumber = Setting::get_by_name('housenumber')->value;

		// Create a payment
		$payment = new \Tigron\Sepa\Payment();
		$payment->paymentInformationIdentification = 'export_' . $this->id;

		$document_date = new DateTime($document->date);
		$now = new DateTime();

		if ($document_date < $now) {
			$document_date = $now;
		}

		$payment->requestedExecutionDate = $document_date;
		$payment->debtorAccount = Setting::get_by_name('iban')->value;
		$payment->debtorAgent = Setting::get_by_name('bic')->value;
		$payment->debtor = $debtor;

		foreach ($documents as $document) {

			// Create a transaction
			$supplier = $document->supplier;
			$creditor = new \Tigron\Sepa\Creditor();
			$creditor->name = $supplier->company;
			$creditor->country = $supplier->country->iso2;
			$creditor->zipcode = $supplier->zipcode;
			$creditor->city = $supplier->city;
			$creditor->street = $supplier->street;
			$creditor->housenumber = $supplier->housenumber;

			$transaction = new \Tigron\Sepa\Transaction();
			$transaction->paymentIdentification = 'document_' . $document->id;
			$transaction->amount = $document->price_incl;
			$transaction->creditorAgent = $supplier->bic;
			$transaction->creditorAccount = $supplier->iban;
			$transaction->creditor = $creditor;
			if ($document->payment_message != '') {
				$transaction->unstructured_message = $document->payment_message;
			} else {
				$transaction->structured_message = $document->payment_structured_message;
			}

			$payment->transactions[] = $transaction;
		}

		$credit->payments[] = $payment;

		if ($data['mark_paid']) {
			foreach ($documents as $document) {
				$document->paid = true;
				$document->save();
			}
		}

		$xml = $credit->render();
		$file = \Skeleton\File\File::store('payment_sepa' . date('Ymd') . '.xml', $xml);
 		$this->file_id = $file->id;
		$this->save();
	}

}
