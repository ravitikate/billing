<style>
    .chat-message {
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 10px;
        max-width: 75%;
    }

    .foot_flex {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* .chat-left {
        background-color: #05cd05;
        align-self: flex-start;
    } */
    .chat-left {
        /* background-color: #05cd0569; */
        align-self: flex-start;
        box-shadow: 3px 3px 1px green;
        border: 1px solid green;

    }

    .chat-right {
        box-shadow: 3px 3px 1px #00a8ff;
        border: 1px solid #00a8ff;
        align-self: flex-end;
    }

    .chat-box {
        display: flex;
        flex-direction: column;
    }

    input#chatInput {
        border: 1px solid #019ed2;
    }

    span.sendBtnn {
        font-size: 23px;
        color: #fff;
        background: #019ed2;
        display: flex;
        width: 50px;
        height: 50px;
        align-items: center;
        justify-content: center;
        line-height: 0;
        cursor: pointer;
    }
    .btnRelative{
        position: relative;
    }
    span.chat-count {
    position: absolute;
    top: -8px;
    background: red;
    line-height: 1;
    font-size: 10px;
    font-weight: 700;
    min-width: 20px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    right: -7px;
}
</style>
<div class="modal fade" id="groupChatModal" tabindex="-1" aria-labelledby="groupChatModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="bill_number"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body" style="height: 400px; overflow-y: auto;">
                  <!-- Chat Messages -->
                  <div id="chatMessages" class="d-flex flex-column chat-box">

                  </div>
              </div>

              <div class="modal-footer">
                  <div class="foot_flex">
                    <input type="text" class="form-control me-2" id="bill_ids" name="bill_id" hidden>
                  <input type="text" class="form-control height-50" id="chatInput" name="chatInput"
                      placeholder="Type your message...">
                  <span class="sendBtnn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></span>
                  </div>
              </div>
          </div>
      </div>
    </div>
