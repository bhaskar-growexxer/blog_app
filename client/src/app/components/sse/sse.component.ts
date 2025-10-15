import { Component, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { SseService } from '../../../services/sse.service';

@Component({
  selector: 'app-sse',
  templateUrl: './sse.component.html',
  styleUrls: ['./sse.component.css']
})
export class SseComponent implements OnInit {

  messages: any[] = [];
  private subscription!: Subscription;

  constructor(private sseService: SseService) {}

  ngOnInit() {
    this.subscription = this.sseService
      .getServerSentEvents('http://localhost:8000/sse')
      .subscribe(
        data => this.messages.push(data),
        err => console.error('SSE error', err)
      );
  }

  ngOnDestroy() {
    this.subscription.unsubscribe(); // Stop listening when component is destroyed
  }

}
