import { Injectable } from '@angular/core';
import { webSocket, WebSocketSubject } from 'rxjs/webSocket';
import { Observable, Subject } from 'rxjs';

export interface ChatMessage {
  type: 'join' | 'system' | 'message';
  msg?: string;
  name?: string;
  room?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ChatSocketService {

  private socket$!: WebSocketSubject<any>;
  private messages$ = new Subject<ChatMessage>();

  /** Connect to websocket */
  connect(userName: string, roomId: string): void {
    this.socket$ = webSocket('ws://localhost:9001');

    this.socket$.subscribe({
      next: (msg) => this.messages$.next(msg),
      error: (err) => console.error('Socket error', err),
      complete: () => console.warn('Socket closed')
    });

    // Send join event
    this.socket$.next({
      type: 'join',
      name: userName,
      room: roomId
    });
  }

  /** Send message */
  sendMessage(text: string): void {
    this.socket$?.next({
      type: 'message',
      msg: text
    });
  }

  /** Message stream */
  onMessage(): Observable<ChatMessage> {
    return this.messages$.asObservable();
  }

  /** Close socket */
  disconnect(): void {
    this.socket$?.complete();
  }
}
