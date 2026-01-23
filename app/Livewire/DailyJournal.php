<?php

namespace App\Livewire;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DailyJournal extends Component
{

    public $date;
    public $content;
    public $mood;
    public $isSaved = false;

    public function mount($date)
    {
        $this->date = $date;
        $this->loadJournal();
    }

    public function loadJournal()
    {
        $entry = JournalEntry::where('user_id', Auth::user()->id)
            ->where('date', $this->date)
            ->first();

        $this->content = $entry->content ?? '';
        $this->mood = $entry->mood ?? null;
    }

    public function save()
    {
        JournalEntry::updateOrCreate(
            ['user_id' => Auth::user()->id, 'date' => $this->date],
            ['content' => $this->content, 'mood' => $this->mood]
        );

        $this->isSaved = true;
        $this->dispatch('journal-saved'); // Para notificación visual si quieres
    }
    public function render()
    {
        return view('livewire.daily-journal');
    }
}
