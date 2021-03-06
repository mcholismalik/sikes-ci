<?php
defined('BASEPATH') or exit('No direct script access allowed');

class JurnalController extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('JurnalModel');
		if (!($this->session->has_userdata('user_id') || $this->session->has_userdata('siswa_id'))) {
			redirect('auth/login');
		}
	}

	public function index()
	{
		$dataHtml1['html']['page'] = $this->load->view('pages/keuangan/page_jurnal', null, true);
		$dataHtml2['html']['page'] = $this->load->view('pages/layout', $dataHtml1, true);
		$dataHtml2['html']['scriptjs'] = 'jurnal';

		$this->load->view('layout', $dataHtml2);
	}

	public function listData()
	{
		// filter
		$cond = [];
		$cond[] = ['show', 'true', 'where'];
		$query = json_decode($this->input->post('query'), true);
		if (!empty($query)) {
			foreach ($query as $k => $v) {
				if($v['name'] == 'date_from') {
					$date = date('Y-m-d', strtotime($v['value']));
					$cond[] = ['date_added >=', $date, 'where'];
				} else if($v['name'] == 'date_until') {
					$date = date('Y-m-d', strtotime($v['value']));
					$cond[] = ['date_added <=', $date, 'where'];
				} else {
					$cond[] = [$v['name'], $v['value'], 'where'];
				}
			}
		}

		// list data
		$listData = $this->JurnalModel->get_datatables($cond);
		$data = [];
		$no = $_POST['start'];
		foreach ($listData as $ld) {
			$no++;
			$row = [];

			$row[] = $no;
			$row[] = $ld->date_added;
			$row[] = ($ld->jurnal_type == 'in') ? 'Pemasukan' : 'Pengeluaran';
			$row[] = 'Rp ' . number_format($ld->total);
			$row[] = $ld->keterangan;
			$row[] = '<td>
								<a class="btn-edit" style="color:#f56954" data-toggle="tooltip" title="Edit" onclick="jurnalModal(' . $ld->t_jurnal_id . ')">
									<i class="fa fa-edit"></i>
								</a>
								<a class="btn-delete" style="color:#f1c40f" data-toggle="tooltip" title="Delete" onclick="deleteRow(\'jurnal_id\', ' . $ld->t_jurnal_id . ', \'jurnal\')">
									<i class="fa fa-trash"></i>
								</a>
							</td>';
			$data[] = $row;
		}
		$output = [
			"draw" => $_POST['draw'],
			"recordsFiltered" => $this->JurnalModel->count_filtered($cond),
			"recordsTotal" => $this->JurnalModel->count_all($cond),
			"data" => $data
		];
		echo json_encode($output);
	}

	public function getData()
	{
		$id = $this->input->get('t_jurnal_id');
		$d = $this->JurnalModel->getJurnalById($id);
		echo json_encode($d);
	}

	public function getSummary($date)
	{ 
		$d = $this->JurnalModel->getJurnalSummary($date);
		echo json_encode($d);
	}

	public function saveData()
	{
		$post = $this->input->post();
		$d = [];
		foreach ($post as $k => $v) {
			if ($k == 'date_added') $v = date('Y-m-d', strtotime($v));
			$d[$k] = $v;
		}
		$affected = ($d['t_jurnal_id'] == 0)
			? $this->JurnalModel->insertJurnal($d)
			: $this->JurnalModel->updateJurnal($d['t_jurnal_id'], $d);

		$res = ($affected) ? true : false;
		echo json_encode($res);
	}
}
