<?php

namespace App\Livewire\Admin;

use App\Models\InstagramStoryPost;
use App\Models\Unit;
use App\Services\InstagramService;
use App\Services\InstagramStoryComposer;
use App\Services\InstagramStoryPublisher;
use Livewire\Component;

/**
 * Halaman "Kirim Story" — satu-satunya tempat story dipublish.
 *
 * Sengaja butuh klik manual: story yang salah tayang tidak bisa dihapus lewat
 * API, jadi keputusan publish tetap di tangan admin.
 */
class InstagramStory extends Component
{
    public $unit_id = '';
    public $caption = '';
    public $previewPath = null;
    public $previewUrl = null;
    public $result = null;
    public $lastError = null;

    public function updatedUnitId()
    {
        $this->reset(['previewPath', 'previewUrl', 'result', 'lastError']);

        if ($this->unit_id !== '') {
            $this->caption = $this->composer()->caption();
        } else {
            $this->caption = '';
        }
    }

    /**
     * Render gambar tanpa mengirim, supaya admin bisa cek dulu tampilannya.
     */
    public function preview()
    {
        $this->reset(['result', 'lastError']);

        try {
            $rendered = $this->composer()->render();
            $this->previewPath = $rendered['path'];
            $this->previewUrl = $rendered['url'];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
        }
    }

    public function publish()
    {
        $this->reset(['result', 'lastError', 'previewPath', 'previewUrl']);

        $unit = Unit::findOrFail($this->unit_id);

        if (!InstagramService::isConfigured()) {
            $this->lastError = 'Access token / IG User ID belum diisi. Isi dulu di Pengaturan → Instagram.';

            return;
        }

        $outcome = app(InstagramStoryPublisher::class)->publish(
            $unit,
            $this->caption,
            auth()->id()
        );

        $this->result = $outcome;

        if ($outcome['ok']) {
            $this->previewUrl = $outcome['image_url'];
            $this->caption = $outcome['post']->caption;
        }
    }

    public function resetForm()
    {
        $this->reset(['unit_id', 'caption', 'previewPath', 'previewUrl', 'result', 'lastError']);
    }

    protected function composer(): InstagramStoryComposer
    {
        return new InstagramStoryComposer(Unit::findOrFail($this->unit_id));
    }

    public function render()
    {
        return view('livewire.admin.instagram-story', [
            'connected' => InstagramService::isConfigured(),
            'units' => Unit::where('is_active', true)->orderBy('seri')->get(),
            'posts' => InstagramStoryPost::with('unit')->latest()->limit(15)->get(),
            'placeholders' => (new InstagramStoryComposer(new Unit()))->tokens(),
        ])->layout('layouts.admin');
    }
}
