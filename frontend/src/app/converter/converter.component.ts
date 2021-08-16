import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-converter',
  templateUrl: './converter.component.html',
  styleUrls: ['./converter.component.scss']
})
export class ConverterComponent implements OnInit {

  public inputPath = './input';
  public outputPath = './output';
  public maxWidth = 3840;
  public maxHeight = 2160;
  public quality = 75;

  constructor() { }

  ngOnInit(): void {
  }

  convert(): void {
  }

}
