import { Component, OnDestroy } from '@angular/core';
import { Subscription } from 'rxjs';
import { ChatSocketService, ChatMessage } from './chat-socket.service';

@Component({
  selector: 'app-chat',
  templateUrl: './chat.component.html',
  styleUrls: ['./chat.component.css']
})
export class ChatComponent implements OnDestroy {

  userName = '';
  roomId = '';
  messageText = '';

  showJoinModal = true;
  showJoinRoomBox = false;

  messages: ChatMessage[] = [];

  private messageSub!: Subscription;

  constructor(private chatSocket: ChatSocketService) {}

  randomRoomId(): string {
    return Math.floor(100000 + Math.random() * 900000).toString();
  }

  showJoinRoom(): void {
    this.showJoinRoomBox = true;
  }

  createRoom(): void {
    if (!this.userName.trim()) {
      alert('Enter your name');
      return;
    }

    this.roomId = this.randomRoomId();
    this.joinRoom();
  }

  joinExistingRoom(): void {
    if (!this.userName.trim() || !this.roomId.trim()) {
      alert('Enter name and room');
      return;
    }

    this.joinRoom();
  }

  private joinRoom(): void {
    this.showJoinModal = false;

    this.chatSocket.connect(this.userName, this.roomId);

    this.messageSub = this.chatSocket
      .onMessage()
      .subscribe(msg => {
        this.messages.push(msg);
        this.scrollToBottom();
      });
  }

  sendMessage(): void {
    if (!this.messageText.trim()) return;

    this.chatSocket.sendMessage(this.messageText);
    this.messageText = '';
  }

  private scrollToBottom(): void {
    setTimeout(() => {
      const box = document.getElementById('chatBox');
      box?.scrollTo(0, box.scrollHeight);
    });
  }

  ngOnDestroy(): void {
    this.messageSub?.unsubscribe();
    this.chatSocket.disconnect();
  }
}
