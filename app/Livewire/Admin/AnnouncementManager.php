<?php

namespace App\Livewire\Admin;

use App\Models\Announcement;
use Livewire\Component;

class AnnouncementManager extends Component
{
    public $message, $type = 'banner', $link_text, $link_url, $style = 'promo', $starts_at, $ends_at, $is_active = true;
    public $ann_id, $isEditing = false, $showModal = false;

    public function render()
    {
        return view('livewire.admin.announcement-manager', [
            'announcements' => Announcement::latest()->get()
        ])->layout('layouts.admin');
    }

    public function create()
    {
        $this->reset(['ann_id', 'message', 'type', 'link_text', 'link_url', 'style', 'starts_at', 'ends_at', 'is_active', 'isEditing']);
        $this->type = 'banner';
        $this->style = 'promo';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $ann = Announcement::findOrFail($id);
        $this->ann_id = $ann->id;
        $this->message = $ann->message;
        $this->type = $ann->type;
        $this->link_text = $ann->link_text;
        $this->link_url = $ann->link_url;
        $this->style = $ann->style;
        $this->starts_at = $ann->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $ann->ends_at?->format('Y-m-d\TH:i');
        $this->is_active = $ann->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'message' => 'required',
            'type' => 'required',
            'style' => 'required',
        ]);

        Announcement::updateOrCreate(
            ['id' => $this->ann_id],
            [
                'type' => $this->type,
                'message' => $this->message,
                'link_text' => $this->link_text,
                'link_url' => $this->link_url,
                'style' => $this->style,
                'starts_at' => $this->starts_at ?: null,
                'ends_at' => $this->ends_at ?: null,
                'is_active' => (bool)$this->is_active,
            ]
        );

        $this->showModal = false;
        session()->flash('message', 'Campaign saved successfully.');
    }

    public function toggleStatus($id)
    {
        $ann = Announcement::findOrFail($id);
        $ann->update(['is_active' => !$ann->is_active]);
    }

    public function delete($id)
    {
        Announcement::findOrFail($id)->delete();
        session()->flash('message', 'Campaign deleted.');
    }

    public function pushNow($id)
    {
        $ann = Announcement::findOrFail($id);
        
        $appId = \App\Models\Setting::getVal('onesignal_app_id');
        $apiKey = \App\Models\Setting::getVal('onesignal_rest_api_key');

        \Illuminate\Support\Facades\Log::info('AnnouncementManager: Attempting Push', [
            'id' => $id,
            'app_id_found' => $appId ? 'YES ('.substr($appId, 0, 8).'...)' : 'NO',
            'api_key_found' => $apiKey ? 'YES ('.substr($apiKey, 0, 8).'...)' : 'NO',
        ]);
        
        $result = \App\Services\OneSignalService::sendToAll(
            $ann->message,
            $ann->style === 'promo' ? 'PROMO SPESIAL! 🎁' : 'INFO PENTING! 📢',
            $ann->link_url
        );

        if ($result['success']) {
            $osId = $result['data']['id'] ?? 'N/A';
            session()->flash('message', "Push sent successfully! OneSignal ID: {$osId}");
        } else {
            session()->flash('error', 'Push failed: ' . $result['message']);
        }
    }
}
