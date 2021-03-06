<?php
defined('BASEPATH') or exit('No direct script access allowed');

class TipeTarifController extends CI_Controller
{
	protected $data;
	public function __construct()
	{
		parent::__construct();
		$this->load->model('TarifTipeModel');
		$this->load->model('TarifNilaiModel');
		$this->load->model('TarifTipeSpesificModel');
		$this->load->model('TahunAjaranModel');
		$this->load->model('TransactionTypeModel');
		$this->load->model('TahunAjaranModel');
		$this->load->model('SiswaModel');
		$this->load->model('PembayaranModel');
		$this->load->model('SyncTarifModel');
		$this->load->model('SiswaModel');
		if (!$this->session->has_userdata('user_id')) {
			redirect('auth/login');
		}
	}

	public function index()
	{
		$data['listTransactionType'] = $this->TransactionTypeModel->getTransactionType();

		$dataHtml1['html']['page'] = $this->load->view('pages/data_master/tipe_tarif/page', $data, true);
		$dataHtml2['html']['page'] = $this->load->view('pages/layout', $dataHtml1, true);
		$dataHtml2['html']['scriptjs'] = 'tarif_tipe';
		$this->load->view('layout', $dataHtml2);
	}

	public function listData()
	{
		// filter
		$cond = [];

		// list data
		$listData = $this->TarifTipeModel->get_datatables($cond);
		$data = [];
		$no = $_POST['start'];
		foreach ($listData as $ld) {
			$no++;
			$row = [];
			$row[] = $no;
			$row[] = $ld->tarif_tipe;
			$row[] = ($ld->transaction_type == 'bulanan')
				? '<small class="label bg-green">' . $ld->transaction_type . '</small>'
				: '<small class="label bg-blue">' . $ld->transaction_type . '</small>';
			$row[] = ($ld->spesific == 1) ?
				'<td>
									<a href="' . base_url() . 'data_master/tipe_tarif/page/tarif_tipe_spesific/' . $ld->tarif_tipe_id . '" style="color:#00a65a" data-toggle="tooltip" title="Detail Spesific">
										<small class="label bg-green"><i class="fa fa-check-circle-o"></i> Ya</small> 
									</a>
								</td>' : '';
			$row[] = '<td>
								<a class="btn-edit" style="color:#f56954" data-toggle="tooltip" title="Edit" onclick="tarifTipeModal(' . $ld->tarif_tipe_id . ')">
									<i class="fa fa-edit"></i>
								</a>
								<a style="color:#00c0ef" data-toggle="tooltip" title="Sync Data Tunggakan" onclick="syncTarif(' . $ld->tarif_tipe_id . ')">
									<i class="fa fa-refresh"></i>
								</a>
								<a href="' . base_url() . 'data_master/tipe_tarif/page/tarif_nilai/' . $ld->tarif_tipe_id . '" style="color:#00a65a" data-toggle="tooltip" title="Detail Data Per Kelas & TA">
									<i class="fa fa-search"></i>
								</a>
								<a class="btn-delete" style="color:#f1c40f" data-toggle="tooltip" title="Delete" onclick="deleteRow(\'tarif_tipe_id\', ' . $ld->tarif_tipe_id . ', \'tarif_tipe\')">
								  <i class="fa fa-trash"></i>
							  </a>
							</td>';
			$data[] = $row;
		}
		$output = [
			"draw" => $_POST['draw'],
			"recordsFiltered" => $this->TarifTipeModel->count_filtered($cond),
			"recordsTotal" => $this->TarifTipeModel->count_all($cond),
			"data" => $data
		];
		echo json_encode($output);
	}

	public function saveData()
	{
		$post = $this->input->post();
		$d = [];
		foreach ($post as $k => $v) {
			$d[$k] = $v;
		}

		$affected = ($d['tarif_tipe_id'] == 0)
			? $this->TarifTipeModel->insertTarifTipe($d)
			: $this->TarifTipeModel->updateTarifTipe($d['tarif_tipe_id'], $d);

		$res = ($affected) ? true : false;
		echo json_encode($res);
	}

	public function getData()
	{
		$id = $this->input->get('tarif_tipe_id');
		$data = $this->TarifTipeModel->getTarifTipeById($id);
		echo json_encode($data);
	}

	// Tarif Nilai
	public function tarif_nilai($tarif_tipe_id)
	{
		$data['listTahunAjaran'] = $this->TahunAjaranModel->getTahunAjaran();
		$data['tarifTipe'] = $this->TarifTipeModel->getTarifTipeById($tarif_tipe_id);

		$dataHtml1['html']['page'] = $this->load->view('pages/data_master/tipe_tarif/tarif_nilai', $data, true);
		$dataHtml2['html']['page'] = $this->load->view('pages/layout', $dataHtml1, true);
		$dataHtml2['html']['scriptjs'] = 'tarif_nilai';

		$this->load->view('layout', $dataHtml2);
	}

	public function listDataTarifNilai($tarif_tipe_id)
	{
		// filter
		$cond = [];
		$cond[] = ['tarif_tipe_id', $tarif_tipe_id, 'where'];

		$nomin = ($this->TarifTipeModel->getTarifTipeById($tarif_tipe_id)['transaction_type_id'] == 2) ? true : false;

		// list data
		$listData = $this->TarifNilaiModel->get_datatables($cond);
		$data = [];
		$no = $_POST['start'];
		foreach ($listData as $ld) {
			$no++;
			$row = [];
			$row[] = $no;
			$row[] = $ld->ta;
			$row[] = $ld->kelas;
			$row[] = 'Rp ' . number_format($ld->nominal);
			if ($nomin) $row[] = 'Rp ' . number_format($ld->nominal_min);
			$row[] = $ld->date_started;
			$row[] = $ld->date_ended;
			$row[] = '<td>
								<a class="btn-delete" style="color:#f1c40f" data-toggle="tooltip" title="Delete" onclick="deleteRow(\'tarif_nilai_id\', ' . $ld->tarif_nilai_id . ', \'tarif_nilai\')">
								  <i class="fa fa-trash"></i>
							  </a>
							</td>';
			$data[] = $row;
		}
		$output = [
			"draw" => $_POST['draw'],
			"recordsFiltered" => $this->TarifNilaiModel->count_filtered($cond),
			"recordsTotal" => $this->TarifNilaiModel->count_all($cond),
			"data" => $data
		];
		echo json_encode($output);
	}

	public function saveDataTarifNilai()
	{
		$post = $this->input->post();
		$permission = true;

		$d = [];
		foreach ($post as $k => $v) {
			if ($k == 'date_started') $v = $this->detectDate(date('Y-m-d', strtotime($v)), 'bef', 'date');
			if ($k == 'date_ended') $v = $this->detectDate(date('Y-m-d', strtotime($v)), 'af', 'date');
			$d[$k] = $v;
		}

		// validation
		if (strtotime($d['date_started']) > strtotime($d['date_ended'])) {
			echo json_encode(false);
			exit;
		}

		if (array_key_exists('active', $d) && $d['active'] == 1) {
			$dCheck = $this->TarifNilaiModel->getTarifNilaiById($d['tarif_nilai_id']);
			$params = [
				'ta_id' => $dCheck['ta_id'],
				'kelas' => $dCheck['kelas'],
				'tarif_tipe_id' => $dCheck['tarif_tipe_id'],
				'active' => 1
			];
			$check = $this->TarifNilaiModel->getTarifNilaiByParam($params);
			if ($check >= 1) $permission = false;
		}


		$affected = 0;
		if ($permission == true) {
			$affected = ($d['tarif_nilai_id'] == 0)
				? $this->TarifNilaiModel->insertTarifNilai($d)
				: $this->TarifNilaiModel->updateTarifNilai($d['tarif_nilai_id'], $d);
		}
		$res = ($affected) ? true : false;
		echo json_encode($res);
	}

	public function getDataTarifNilai()
	{
		$id = $this->input->get('tarif_nilai_id');
		$data = $this->TarifNilaiModel->getTarifNilaiById($id);
		echo json_encode($data);
	}

	// Tarif Nilai Spesific
	public function tarif_tipe_spesific($tarif_tipe_id)
	{
		$data['listSiswa'] = $this->SiswaModel->getSiswaByParam([]);
		$data['tarifTipe'] = $this->TarifTipeModel->getTarifTipeById($tarif_tipe_id);

		$dataHtml1['html']['page'] = $this->load->view('pages/data_master/tipe_tarif/tarif_tipe_spesific', $data, true);
		$dataHtml2['html']['page'] = $this->load->view('pages/layout', $dataHtml1, true);
		$dataHtml2['html']['scriptjs'] = 'tarif_tipe_spesific';

		$this->load->view('layout', $dataHtml2);
	}

	public function listDataTarifTipeSpesific($tarif_tipe_id)
	{
		// filter
		$cond = [];
		$cond[] = ['tarif_tipe_id', $tarif_tipe_id, 'where'];

		// list data
		$listData = $this->TarifTipeSpesificModel->get_datatables($cond);
		$data = [];
		$no = $_POST['start'];
		foreach ($listData as $ld) {
			$no++;
			$row = [];
			$row[] = $no;
			$row[] = $ld->nis;
			$row[] = $ld->nama;
			$row[] = '<td>
								<a class="btn-delete" style="color:#f1c40f" data-toggle="tooltip" title="Delete" onclick="deleteRow(\'tarif_tipe_spesific_id\', ' . $ld->tarif_tipe_spesific_id . ', \'tarif_tipe_spesific\')">
								  <i class="fa fa-trash"></i>
							  </a>
							</td>';
			$data[] = $row;
		}
		$output = [
			"draw" => $_POST['draw'],
			"recordsFiltered" => $this->TarifNilaiModel->count_filtered($cond),
			"recordsTotal" => $this->TarifNilaiModel->count_all($cond),
			"data" => $data
		];
		echo json_encode($output);
	}

	public function saveDataTarifTipeSpesific()
	{
		$post = $this->input->post();

		$d = [];
		foreach ($post as $k => $v) {
			$d[$k] = $v;
		}
		$affected = $this->TarifTipeSpesificModel->insertTarifTipeSpesific($d);
		$res = ($affected) ? true : false;

		echo json_encode($res);
	}

	// Sync Tarif
	public function sync($tarif_tipe_id)
	{
		$dTarifNilai = $this->TarifNilaiModel->getTarifNilaiByTarifTipeId($tarif_tipe_id);
		$dTarifTipe = $this->TarifTipeModel->getTarifTipeById($tarif_tipe_id);

		if ($dTarifTipe['spesific'] == 1) {
			$arrSiswa = [];
			$dTarifTipeSpesific = $this->TarifTipeSpesificModel->getTarifTipeSpesificByParam(['tarif_tipe_id' => $tarif_tipe_id]);
			foreach ($dTarifTipeSpesific as $dts) {
				$arrSiswa[] = $dts['siswa_id'];
			}
			$param_in = ['siswa_id' => $arrSiswa];
		}

		foreach ($dTarifNilai as $tn) {
			$param = ['ta_id' => $tn['ta_id'], 'kelas' => $tn['kelas'], 'active' => 1];
			if ($dTarifTipe['spesific'] == 0) $param_in = null;
			$dSiswa =  $this->SiswaModel->getSiswaByParam($param, $param_in);

			// bulanan
			if ($tn['transaction_type_id'] == 1) {
				$this->tarifBulanan($dSiswa, $tn);
				// cicilan
			} else {
				$this->tarifCicilan($dSiswa, $tn);
			}
		}
		echo json_encode(true);
	}

	private function tarifBulanan($dSiswa, $tn)
	{
		$date1 = strtotime($tn['date_started']);
		$date2 = strtotime($tn['date_ended']);

		$loop = true;
		while ($loop) {
			$d = date('Y-m-d', $date1);
			$dExpl = explode('-', $d);

			foreach ($dSiswa as $sis) {
				$check = $this->checkSyncTarif($sis['siswa_id'], $tn['tarif_nilai_id'], $dExpl[0], $dExpl[1]);
				if (!$check) {
					$this->insertPembayaran($sis['siswa_id'], $tn, $dExpl[0], $dExpl[1]);
					$this->insertSyncTarif($sis['siswa_id'], $tn, $dExpl[0], $dExpl[1]);
				}
			}

			$date1 = strtotime('+1 MONTH', $date1);
			$loop = ($date1 <= $date2) ? true : false;
		}
	}

	private function tarifCicilan($dSiswa, $tn)
	{
		foreach ($dSiswa as $sis) {
			$check = $this->checkSyncTarif($sis['siswa_id'], $tn['tarif_nilai_id']);
			if (!$check) {
				$this->insertPembayaran($sis['siswa_id'], $tn);
				$this->insertSyncTarif($sis['siswa_id'], $tn);
			}
		}
	}

	private function detectDate($date, $type, $returnType = 'str')
	{
		$expl = explode('-', $date);
		if ($type == 'bef') {
			$m = ($expl[2] > 10) ? $expl[1] + 1 : $expl[1];
		} else {
			$m = ($expl[2] < 10) ? $expl[1] - 1 : $expl[1];
		}
		$date = $expl[0] . '-' . $m . '-10';
		return ($returnType == 'date') ? $date : strtotime($date);
	}

	private function insertPembayaran($siswa_id, $tn, $thn = 0, $bln = 0)
	{
		$dPmbyr = [
			'siswa_id' => $siswa_id,
			'tarif_nilai_id' => $tn['tarif_nilai_id'],
			'tahun' => $thn,
			'bulan_ke' => $bln,
			'nominal' => $tn['nominal'],
			'nominal_min' => $tn['nominal_min'],
			'nominal_bayar' => 0,
			'date_added' => date('Y-m-d H:i:s'),
			'date_modified' => date('Y-m-d H:i:s'),
			'created_by' => $this->session->userdata('user_id')
		];
		$ins = $this->PembayaranModel->insertPembayaran($dPmbyr);
	}

	private function checkSyncTarif($siswa_id, $tarif_nilai_id, $thn = null, $bln = null)
	{
		$param = [
			'siswa_id' => $siswa_id,
			'tarif_nilai_id' => $tarif_nilai_id,
			'status' => 1
		];

		if ($thn != null) $param['tahun'] = $thn;
		if ($bln != null) $param['bulan_ke'] = $bln;
		$check = $this->SyncTarifModel->getSyncTarifByParam($param);
		return $check;
	}

	private function insertSyncTarif($siswa_id, $tn, $thn = null, $bln = null)
	{
		$dSyncTarif = [
			'siswa_id' => $siswa_id,
			'tarif_nilai_id' => $tn['tarif_nilai_id'],
			'status' => 1,
			'date_added' => date('Y-m-d H:i:s'),
			'created_by' => $this->session->userdata('user_id')
		];

		if ($thn != null) $dSyncTarif['tahun'] = $thn;
		if ($bln != null) $dSyncTarif['bulan_ke'] = $bln;
		$ins = $this->SyncTarifModel->insertSyncTarif($dSyncTarif);
	}
}
