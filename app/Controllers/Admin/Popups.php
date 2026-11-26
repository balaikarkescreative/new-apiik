<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PopupModel;

class Popups extends BaseController
{
    protected $popupModel;
    
    public function __construct()
    {
        $this->popupModel = new PopupModel();
    }
    
    public function index()
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $data = [
            'title' => 'Kelola Popup',
            'popups' => $this->popupModel->getPopupsWithPagination(10),
            'pager' => $this->popupModel->pager
        ];
        
        return view('admin/popups/index', $data);
    }
    
    public function create()
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $data = [
            'title' => 'Tambah Popup Baru'
        ];
        
        return view('admin/popups/create', $data);
    }
    
    public function store()
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|max_length[255]',
            'content' => 'required',
            'button_text' => 'required|max_length[100]',
            'button_url' => 'permit_empty|valid_url_strict',
            'delay_seconds' => 'required|integer|greater_than_equal_to[0]',
            'auto_close_seconds' => 'permit_empty|integer|greater_than[0]',
            'image' => 'permit_empty|uploaded[image]|max_size[image,2048]|is_image[image]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }
        
        $data = [
            'title' => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'button_text' => $this->request->getPost('button_text'),
            'button_url' => $this->request->getPost('button_url') ?: null,
            'delay_seconds' => $this->request->getPost('delay_seconds'),
            'auto_close_seconds' => $this->request->getPost('auto_close_seconds') ?: null,
            'show_once' => $this->request->getPost('show_once') ? 1 : 0,
            'show_title' => $this->request->getPost('show_title') ? 1 : 0,
            'show_content' => $this->request->getPost('show_content') ? 1 : 0,
            'show_on_refresh' => $this->request->getPost('show_on_refresh') ? 1 : 0,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0
        ];
        
        // Handle image upload
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $image->move(ROOTPATH . 'public/uploads/popup', $newName);
            $data['image'] = $newName;
        }
        
        // If this popup is set to active, deactivate others
        if ($data['is_active']) {
            $this->popupModel->deactivateOthers();
        }
        
        if ($this->popupModel->insert($data)) {
            return redirect()->to('/admin/popups')->with('success', 'Popup berhasil ditambahkan');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan popup');
        }
    }
    
    public function edit($id)
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $popup = $this->popupModel->find($id);
        if (!$popup) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Popup tidak ditemukan');
        }
        
        $data = [
            'title' => 'Edit Popup',
            'popup' => $popup
        ];
        
        return view('admin/popups/edit', $data);
    }
    
    public function update($id)
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $popup = $this->popupModel->find($id);
        if (!$popup) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Popup tidak ditemukan');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|max_length[255]',
            'content' => 'required',
            'button_text' => 'required|max_length[100]',
            'button_url' => 'permit_empty|valid_url_strict',
            'delay_seconds' => 'required|integer|greater_than_equal_to[0]',
            'auto_close_seconds' => 'permit_empty|integer|greater_than[0]',
            'image' => 'permit_empty|uploaded[image]|max_size[image,2048]|is_image[image]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }
        
        $data = [
            'title' => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'button_text' => $this->request->getPost('button_text'),
            'button_url' => $this->request->getPost('button_url') ?: null,
            'delay_seconds' => $this->request->getPost('delay_seconds'),
            'auto_close_seconds' => $this->request->getPost('auto_close_seconds') ?: null,
            'show_once' => $this->request->getPost('show_once') ? 1 : 0,
            'show_title' => $this->request->getPost('show_title') ? 1 : 0,
            'show_content' => $this->request->getPost('show_content') ? 1 : 0,
            'show_on_refresh' => $this->request->getPost('show_on_refresh') ? 1 : 0,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0
        ];

        // Handle image upload
        $imageFile = $this->request->getFile('image');
        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            // Delete old image if exists
            if ($popup['image'] && file_exists(ROOTPATH . 'public/uploads/popup/' . $popup['image'])) {
                unlink(ROOTPATH . 'public/uploads/popup/' . $popup['image']);
            }
            
            $newName = $imageFile->getRandomName();
            $imageFile->move(ROOTPATH . 'public/uploads/popup', $newName);
            $data['image'] = $newName;
        }
        
        // If this popup is set to active, deactivate others
        if ($data['is_active']) {
            $this->popupModel->deactivateOthers($id);
        }
        
        if ($this->popupModel->update($id, $data)) {
            return redirect()->to('/admin/popups')->with('success', 'Popup berhasil diperbarui');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui popup');
        }
    }
    
    public function delete($id)
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/admin/login');
        }
        
        $popup = $this->popupModel->find($id);
        if (!$popup) {
            return redirect()->to('/admin/popups')->with('error', 'Popup tidak ditemukan');
        }
        
        // Delete image file if exists
        if ($popup['image'] && file_exists(ROOTPATH . 'public/uploads/popup/' . $popup['image'])) {
            unlink(ROOTPATH . 'public/uploads/popup/' . $popup['image']);
        }
        
        if ($this->popupModel->delete($id)) {
            return redirect()->to('/admin/popups')->with('success', 'Popup berhasil dihapus');
        } else {
            return redirect()->to('/admin/popups')->with('error', 'Gagal menghapus popup');
        }
    }
    
    public function toggleStatus($id)
    {
        // Check if user is logged in and has admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }
        
        $popup = $this->popupModel->find($id);
        if (!$popup) {
            return $this->response->setJSON(['success' => false, 'message' => 'Popup tidak ditemukan']);
        }
        
        $newStatus = $popup['is_active'] == 1 ? 0 : 1;
        
        // If activating this popup, deactivate others
        if ($newStatus == 1) {
            $this->popupModel->deactivateOthers($id);
        }
        
        if ($this->popupModel->update($id, ['is_active' => $newStatus])) {
            $statusText = $newStatus == 1 ? 'Aktif' : 'Nonaktif';
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Status popup berhasil diubah menjadi ' . $statusText,
                'status' => $newStatus
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengubah status popup']);
        }
    }
}
