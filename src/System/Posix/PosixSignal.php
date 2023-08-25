<?php

namespace LogServer\System\Posix;

enum PosixSignal: int {

  case SIGHUP  = 1;   // Hangup signal
  case SIGINT  = 2;   // Interrupt signal
  case SIGQUIT = 3;   // Quit signal
  case SIGILL  = 4;   // Illegal instruction signal
  case SIGABRT = 6;   // Abort signal
  case SIGKILL = 9;   // Kill signal
  case SIGSEGV = 11;  // Segmentation violation signal
  case SIGPIPE = 13;  // Broken pipe signal
  case SIGALRM = 14;  // Alarm signal
  case SIGTERM = 15;  // Termination signal
  case SIGUSR1 = 10;  // User-defined signal 1
  case SIGUSR2 = 12;  // User-defined signal 2
  case SIGCHLD = 17;  // Child process status changed signal
  case SIGCONT = 18;  // Continue signal
  case SIGSTOP = 19;  // Stop signal
  case SIGTSTP = 20;  // Terminal stop signal
  case SIGTTIN = 21;  // Terminal input signal
  case SIGTTOU = 22;  // Terminal output signal

};
