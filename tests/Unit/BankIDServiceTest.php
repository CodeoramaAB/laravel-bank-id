<?php

namespace Jgroup\BankID\Tests;

use Carbon\Carbon;
use Jgroup\BankID\RpApi\RpApi;
use Jgroup\BankID\RpApi\Models\UserData;
use Jgroup\BankID\Service\BankIDService;
use Jgroup\BankID\Service\Models\Status;
use Jgroup\BankID\RpApi\Models\CompletionData;
use Jgroup\BankID\RpApi\Models\CollectResponse;
use Jgroup\BankID\Service\Models\BankIDTransaction;
use Jgroup\BankID\RpAPi\Models\StartTransactionResponse;

class BankIDServiceTest extends TestCase
{
    const CLIENT_IP = '127.0.0.1';

    const FAKE_TIME = 1661859281;

    protected $signature =
        'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PFNpZ25hdHVyZSB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+PFNpZ25lZEluZm8geG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiPjxDYW5vbmljYWxpemF0aW9uTWV0aG9kIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvVFIvMjAwMS9SRUMteG1sLWMxNG4tMjAwMTAzMTUiPjwvQ2Fub25pY2FsaXphdGlvbk1ldGhvZD48U2lnbmF0dXJlTWV0aG9kIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8wNC94bWxkc2lnLW1vcmUjcnNhLXNoYTI1NiI+PC9TaWduYXR1cmVNZXRob2Q+PFJlZmVyZW5jZSBUeXBlPSJodHRwOi8vd3d3LmJhbmtpZC5jb20vc2lnbmF0dXJlL3YxLjAuMC90eXBlcyIgVVJJPSIjYmlkU2lnbmVkRGF0YSI+PFRyYW5zZm9ybXM+PFRyYW5zZm9ybSBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnL1RSLzIwMDEvUkVDLXhtbC1jMTRuLTIwMDEwMzE1Ij48L1RyYW5zZm9ybT48L1RyYW5zZm9ybXM+PERpZ2VzdE1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvMDQveG1sZW5jI3NoYTI1NiI+PC9EaWdlc3RNZXRob2Q+PERpZ2VzdFZhbHVlPlJEVHVqTFo2ZHkyK3hiaUk1aEg0WlJPeHhMbHZVY0lVckxEajRTRjVYZUE9PC9EaWdlc3RWYWx1ZT48L1JlZmVyZW5jZT48UmVmZXJlbmNlIFVSST0iI2JpZEtleUluZm8iPjxUcmFuc2Zvcm1zPjxUcmFuc2Zvcm0gQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy14bWwtYzE0bi0yMDAxMDMxNSI+PC9UcmFuc2Zvcm0+PC9UcmFuc2Zvcm1zPjxEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiPjwvRGlnZXN0TWV0aG9kPjxEaWdlc3RWYWx1ZT5XVGw5Zlc0NFRELzA0MHlOZ2h5MnlRNWZXeUgzZ29JNGFYd1R2Um1vVnlBPTwvRGlnZXN0VmFsdWU+PC9SZWZlcmVuY2U+PC9TaWduZWRJbmZvPjxTaWduYXR1cmVWYWx1ZT5mY3MzZm45RjQyZDJGYk96S3FpYVdvWjF1UGtVSU9JYmh6SytsU2owVE9HRHJoNW11MGZ4SXhJbHQvanI4NkxGaVhjbHVsN3N3Y3dxYk1CYXU5NGpKbmJNby9oMjBmS2ZPY0IxTStaNmswamtMYlFlOS8zdktqZVMzYXJHdnFXNTFIVVpwOFVnOXlKWEFucVNTTkV5TXQ5VWE5TmtrUHhJemw1aFRBbkFOTkZyMms1cjZMdldiYnhpWitJeVdGZWl3UG5semhQeEpmdmNQMExySDJxZW50M3lnV1diMFd0dTVtMVd6RFBoL2pGUy9TamRrMlQzUlhTNmhFOW1TaVh6WDYxU1I3QUNwZnlCV3IzY2I0UFp5ZlFDVVVBWVlBbFgyeFRGV3hFenVvdHJuaXpMYXh4eHBuUysvSGwxeVpYY012UjJLUm4ybGpPd0dZMjZBYU12OGc9PTwvU2lnbmF0dXJlVmFsdWU+PEtleUluZm8geG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiIElkPSJiaWRLZXlJbmZvIj48WDUwOURhdGE+PFg1MDlDZXJ0aWZpY2F0ZT5NSUlGWURDQ0EwaWdBd0lCQWdJSUh2V01SUnBUNnNrd0RRWUpLb1pJaHZjTkFRRUxCUUF3ZURFTE1Ba0dBMVVFQmhNQ1UwVXhIVEFiQmdOVkJBb01GRlJsYzNSaVlXNXJJRUVnUVVJZ0tIQjFZbXdwTVJVd0V3WURWUVFGRXd3eE1URXhNVEV4TVRFeE1URXhNekF4QmdOVkJBTU1LbFJsYzNSaVlXNXJJRUVnUTNWemRHOXRaWElnUTBFeElIWXhJR1p2Y2lCQ1lXNXJTVVFnVkdWemREQWVGdzB5TWpBNU1USXlNakF3TURCYUZ3MHlNekE1TVRNeU1UVTVOVGxhTUlIQk1Rc3dDUVlEVlFRR0V3SlRSVEVkTUJzR0ExVUVDZ3dVVkdWemRHSmhibXNnUVNCQlFpQW9jSFZpYkNreEV6QVJCZ05WQkFRTUNsUnZiSFpoYm5OemIyNHhEekFOQmdOVkJDb01CbFJ2YkhaaGJqRVZNQk1HQTFVRUJSTU1NVGt4TWpFeU1USXhNakV5TVRvd09BWURWUVFwRERFb01qSXdPVEV6SURBNExqTXpLU0JVYjJ4MllXNGdWRzlzZG1GdWMzTnZiaUF0SUVKaGJtdEpSQ0J3dzZVZ1ptbHNNUm93R0FZRFZRUUREQkZVYjJ4MllXNGdWRzlzZG1GdWMzTnZiakNDQVNJd0RRWUpLb1pJaHZjTkFRRUJCUUFEZ2dFUEFEQ0NBUW9DZ2dFQkFQTmFldkdLVW5yc20yMkFVaVd6UDd0M2JsQ0RJbEdpUHhmRDhvd2NpRFlDaU5rRVBUTlpiekRNVVk2Q0pmZ2ZOa24xaUw4OUhMQ0J2WFpDVVRYMlRzMklJV2ZiL2ZrM2NsUHR3V1MvSEtUWlhJMEJaT3gxdU1YVCtPOXZ6aUlMdkhoRklpelFkSkJqOGpoTHhXYi9EbGFRRGNSMWZLanIwZFp4aFlzQkF0dEo1eHN6T2E3WE5neTRjU0o4WnRid3M3VHFrRlR3bzJCankzdTA4UmFzKzNaQUZwcHpWb1hubUs4TE15QTkyNnhYOHNPZHA3QVNFUGI0eHdpb3RhOVY4ZTdEQlV0OENiTG13SmhkcFkwbHZNTVBKUDYwVXNTbk13NUJMNC96WDJQc0hURE16aFBFOGtHS0d0VXZrOGNPRlFieE92eE1VN0dWa1pIWS9yMENhSWtDQXdFQUFhT0JvekNCb0RBN0JnZ3JCZ0VGQlFjQkFRUXZNQzB3S3dZSUt3WUJCUVVITUFHR0gyaDBkSEE2THk5MFpYTjBMbkpsZG05allYUnBiMjV6ZEdGMGRYTXVjMlV3RVFZRFZSMGdCQW93Q0RBR0JnUXFBd1FGTUE0R0ExVWREd0VCL3dRRUF3SUhnREFmQmdOVkhTTUVHREFXZ0JSZ2VuMm5XWU9NbjZTeEYrb05RME9WUSthWi9UQWRCZ05WSFE0RUZnUVVnVXVPTytFMHozNDNZdHFGUE4yTFl5QU1pVll3RFFZSktvWklodmNOQVFFTEJRQURnZ0lCQUN4cFlKdnovUVVzTzUrQXRjaHN2aUtHNWpiUkJqN2tqRUxOcmUxV2EyOU9IK1ZxSHFTaEhzUlg5SDlVK0xpbG81NGhXcUxhTTFyWTV5OFAzWU5FS3VXbHpvZS9sQlpCVTF3K2V3clYwRjk2UmRKUDZncGVUZVVLeG0zRFlTeFdldDJsZkJVMHhkT21LdFF6OENPUnBYL09RajNmR3ppRlgzdlIxeDREdVIxcm9sdjIwSlIxTGE3QjMrMXhpTUNpUXdQKzBsOGZLaDA3L1h4RFJQMUpwM3paQ2Z5NTNZazdHN3R3eXgzck5XR0l6MXQ2VG9MTmFWK1NBNlJwcTdDN3dzdjE3YXM5Tnh6Vk1oZEZxaHdzaDBKWlFabVhMZkJUazE5Z3pDNllvbHpMZ0hmTmhaem9oNmEzSTBNTWlhUjVoYXM3TnYrWms3VFFZaGRienlYRTI1UW1MUi9ZTmVJWEpNUk5aY2x1WFJzU3JzbUlhbGtBQ0tVNTh5bGJyQW0rTDBVRlJxRGFUZlVzTUlXcWVkK0U4SHhRbjVZQTl1NDlSTXVGR3o3SlB6SmlyREhpRUhpc3ZSQlNZZjhma2pqbGVWRktMQTYySEcrNUdMTGtNY0pmZUZJNXpkd3NybjFwdU1qM2REUXFOUy95MDhOb09PaDArWmYyRHdxdzRhZVpHaDNYSm1ralFrOGFubTIxV2lvcFI1MXNmSSt5dmFwaWNXdGlUZVRkbGJmQSsraXdLd3FvcU9UcXhPbXlIa1V1Q09VK3I3RFVPb2gzY2Y4aVlGRU5VbHBqNmJCTDRvdytsZEUyOCtFUnpsQzVCcFlFczlKZGtxZUFBaDg1TmZ1SjgyMEFFTm8wTDQvSm9RSG12OTB4aGVBUm1jTlo4b2hTTWJVbjMxSjRpM0QwPC9YNTA5Q2VydGlmaWNhdGU+PFg1MDlDZXJ0aWZpY2F0ZT5NSUlGM2pDQ0E4YWdBd0lCQWdJSUZuWlZ5ZWhtWFl3d0RRWUpLb1pJaHZjTkFRRU5CUUF3YmpFTE1Ba0dBMVVFQmhNQ1UwVXhIVEFiQmdOVkJBb01GRlJsYzNSaVlXNXJJRUVnUVVJZ0tIQjFZbXdwTVJVd0V3WURWUVFGRXd3eE1URXhNVEV4TVRFeE1URXhLVEFuQmdOVkJBTU1JRlJsYzNSaVlXNXJJRUVnUTBFZ2RqRWdabTl5SUVKaGJtdEpSQ0JVWlhOME1CNFhEVEV4TURreU1qRTBNakV4TkZvWERUTTBNVEl3TVRFME1qRXhORm93ZURFTE1Ba0dBMVVFQmhNQ1UwVXhIVEFiQmdOVkJBb01GRlJsYzNSaVlXNXJJRUVnUVVJZ0tIQjFZbXdwTVJVd0V3WURWUVFGRXd3eE1URXhNVEV4TVRFeE1URXhNekF4QmdOVkJBTU1LbFJsYzNSaVlXNXJJRUVnUTNWemRHOXRaWElnUTBFeElIWXhJR1p2Y2lCQ1lXNXJTVVFnVkdWemREQ0NBaUl3RFFZSktvWklodmNOQVFFQkJRQURnZ0lQQURDQ0Fnb0NnZ0lCQUlXMERQb3BMRWh0YXdWUndOckU0MzFHVnNoL0huV1ZzWGRnT2p6VXNEN1FEMzAvdGZPSFJPUWk5bkx1RFdrWTFmRVV4WjA2WXE1THRST29GcGtUUTZTUmkyUmdpVWt1Q05xTUV3c2oyZWlhN0toWVJJay9YSmtrRnAxQnZFNjJJNjN2dFV6WnpTNjlIQXNNTlBsZmRMVTJwSVoyQW5kMlFKMmRDMHhpbW1Galk1azUvejcvTmszSkdCYmF4TEgvWDZ6aGNOcU9wcjJTcnY5RytsaytHdnk3aFFMSW1OTFJWKzRHM21hbEhqNlFNK3dEY1JLdlQ0VitpUmR2elA5bzgwMy9nK0dMNXFpdWZXNlJkVCsybHdHaWZQMmQzc3VMNzl1R1cxSE84cWJpaS9pNEhUeERmdEtkWEZzbEZyWGZSKytRVVU0Qit2NlF5YjRyRjNxaERmZWFrZ2ZMOHV6ZnRNdFRNUmxvd3hJYjA4anhDZWhDU2FZMENNQkhRVFMwTHRYMUMvVmpNNlViYnBTYTI4MHpTTCt4WGxTN1M3MjdzSkI3MjJmeldSMy9OU3AzTVpUYkUwUUFxTVRFTlk0cGZ3Yy9sWHdWbjhUdkFOdzFGSXhFN2lrd0lCTUZTbzZlWDJVRER6OWFpNmR6UnJZZnRJNDRFdExUdjNLVjVVRFdjSWJzUkJ2bGdCUXFxdXBoY3VSVnYxYTZYbzl4ZUgyK28rQnNyK3NvdW1pQzZ6SUZ1VXVCeEI0dXFzU3FlVlFGa0lhZXBpbndoWDVDSkJaTGNPUmFNWkY2STFrR3ZFRFpPVllYT0V0OVBXZy9Tc1NjR00rc2YyNTEwR3owZjJvbVFqT0w1QmV6ZFlZS05Bd3ppejlVMUlyMVZwdnprSkY0U0EzVzA1Y21qaktaQWdNQkFBR2pkakIwTUIwR0ExVWREZ1FXQkJSZ2VuMm5XWU9NbjZTeEYrb05RME9WUSthWi9UQVBCZ05WSFJNQkFmOEVCVEFEQVFIL01COEdBMVVkSXdRWU1CYUFGS1B5ZUhrZEswV0t5ZUhLbFFubG5tL095MDdGTUJFR0ExVWRJQVFLTUFnd0JnWUVLZ01FQlRBT0JnTlZIUThCQWY4RUJBTUNBUVl3RFFZSktvWklodmNOQVFFTkJRQURnZ0lCQUR4aHl6V1N6b2t5RytoVUNwM1VnN1FaeGJNTEsrNklZcCs4YWNSdVRTRmZyNW1hSDNNcnlkODcvQjJ5OUszZlcrRlhRTHBkaFZIb3ZLSk9BUXl2L3QzQ0E2MlpHcnpoQVhHcUNjUjlTbjQ0ZWNLUkpQRTlaSmJ6YWxvNHd0S1JVdjA0VzJaZ0Z1bllUTjU1VHNObjNiR3pjSWlBZGRNcTlUTUt3SWpsNnA1aTZvSWpBbXQ5Lzc1UWY3cVEvMXgyMEVVZHN2KzhRUElwMXZsQjh2QXpBdG8rOGJaRkNSc2RNVkxSUms5NkNvUzUzdjRhRFlZQU14bXNUYmd2THFWVTUvQ05mVkVnVmVTcEZWU3o2ZmxiRk1CWmQ1TE9QZ2xpL2xSSjdGV2V3UXZyWmFLZ2ZKZ2RtVVV2Q3BpMGVEKy9LQm5zRUpMYmhkbksvQitpVG80QTZCd29SKzlYaE9ReU5NVEIvU0R0U1ljekozNXZGaFpmS0o1LzBwc3FYU0pILzI1d0E0cGUvMzRFUnpRMW1nbGFkdDZKT2huV2Y5Mkp3NWpkdzdCRnB0ZzdsbUlrRHlZRFUrNlJ5RXNBckNpYkkrMjh5RjUvZkNaQ3VVZHdEdzlpSHBvb2RmMWg4dDFnZlBubm1rY3dHVGZQZy9kdVVna0Z3S1k5N1N6ZlpnUjAyaGQ3eHhvNXBLNzljemltTUYyR1RGdzlTV1NubFpLNzFmb1kyNUZ6U1VITm11R0hoRnpHOThBRkl0MFZMd2lUajh0SmVTalRpNDFpZjIzN3ZETnZzZXB0KzgvdHQ4MC9mNDVLelBOZldVQjA2L0ZHcjB3Zm9ZZ1pwNFBpOVJSVFh6RGFmd2o3cUxkdWFlcFJyTGNFVXBYV0NHcnVTVXlseHhDaGRCVHdWelpuPC9YNTA5Q2VydGlmaWNhdGU+PFg1MDlDZXJ0aWZpY2F0ZT5NSUlGMHpDQ0E3dWdBd0lCQWdJSVVZbWZkdHF0eTgwd0RRWUpLb1pJaHZjTkFRRU5CUUF3YlRFa01DSUdBMVVFQ2d3YlJtbHVZVzV6YVdWc2JDQkpSQzFVWld0dWFXc2dRa2xFSUVGQ01SOHdIUVlEVlFRTERCWkNZVzVyU1VRZ1RXVnRZbVZ5SUVKaGJtdHpJRU5CTVNRd0lnWURWUVFEREJ0VVpYTjBJRUpoYm10SlJDQlNiMjkwSUVOQklIWXhJRlJsYzNRd0hoY05NVEV3T1RJeU1UUXhOVEF6V2hjTk16UXhNak14TVRRd01UTXpXakJ1TVFzd0NRWURWUVFHRXdKVFJURWRNQnNHQTFVRUNnd1VWR1Z6ZEdKaGJtc2dRU0JCUWlBb2NIVmliQ2t4RlRBVEJnTlZCQVVURERFeE1URXhNVEV4TVRFeE1URXBNQ2NHQTFVRUF3d2dWR1Z6ZEdKaGJtc2dRU0JEUVNCMk1TQm1iM0lnUW1GdWEwbEVJRlJsYzNRd2dnSWlNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0SUNEd0F3Z2dJS0FvSUNBUUNUcVU3dXhrNVF6YlhTNkFyWElHVFdOZVpYejY1YnpkZ294Yjc5THZZaC9wN2tjSzI1bUEydHpHcE8zUVMxZUtKSnU4NEc5VU56bTRtTWw2Y25nblhjanhFVFlpRXF0aWpyQTVtZno4NjUvWDZVZ09wWDdEa291UThkNWVEeWhKNDlVckRxbHJnb1ZNeDMyMmtNMFNaNGhlVmVYODNlMUlTRml5eHFaQkt4aDI1eUtZRVpBNEV6SXJEajJ0aThDUnJXUEhDVFdhSUZwY2Q1VHlNaHBVVFBuNER6d1BoUEdXTVJOeGdPQWVQNEJTREI3UjZhejRyb3g3VFBrZDJzV0cxT0RqLzBJUlBoSlMxZFExQjdRaU5IWTU4UmpuTlRoRVFLd2RXV01QTUtQdGhTZCtHRWpMOUdEYWZZeE9zSXJLRll3bFlOQlczQzVtYmUzVCszaitBeGo2VzJIYmdtSlhQR0l0THVjeFkxa1B3VDlMN3U1bkl4YVJPbWgxdVR3WXFyOXB1R3E2c29KbmdnRVMzSzRQSWhNNmthbXZuQ0NQWG9xV0NDcnVTRVBWZ3lFWkVpMHNoeSs4MVFzZWIxZ2M5cllnVnJFbkxCT0l5TXFhVHRFeGFGcHJZYnYxZi9Bd1d0akZVaTJYaVNkTjhhTXAra3FiaSsxdEtKVVVQTEMrQ3JkdTlmRm8vOGxzbFNkZXcrU25QVkZlVno1Q09LYnQ2R1RFNHhjSmVSelc1d1EwdzdiK3JHTFdoSnZ3UkpzUzVHWHZxYTNMZzhFeVdpTEpzd3VURmFFd1BVRHZaQnZ5RlpFWmVydEtnWmJSWXZlem85L2dyd3lCK21vclZyTHJ5dTljaFlFWXdFNTUwdXp5S3R6WFV6eWdWOEZwWGU5RHBtcE9TZkdNQVVSUUlEQVFBQm8zWXdkREFkQmdOVkhRNEVGZ1FVby9KNGVSMHJSWXJKNGNxVkNlV2ViODdMVHNVd0R3WURWUjBUQVFIL0JBVXdBd0VCL3pBZkJnTlZIU01FR0RBV2dCUks5Nk5xQ05vSU9CY1pVeWpJMnFiV05OaGF1akFSQmdOVkhTQUVDakFJTUFZR0JDb0RCQVV3RGdZRFZSMFBBUUgvQkFRREFnRUdNQTBHQ1NxR1NJYjNEUUVCRFFVQUE0SUNBUURQMURveGpFamV5RzI3eGVhaSttcHh4Sm9xQjFSRFZURVk4NlJkTnlsdVVLUU9JYmZLSk1tWCtEWDR2VHVVUVMzNTM5eHpIS3dwajZnaytpWlZqRjFVb0p0R3ArcXVyamphck9oNDRzKytzMHlXS2lLckpCRWxvSm44bytZWEZUOEM3ZTFXdHFKVm9hRmREQkN2b2hKeUsyMFBLUzcvblVHNWI3SjZpcTM1MTdZdmpiNEQ5NEx0MGRITlNnRDJCSUlIbU5rcFNZV2d5aTFzZWF2aE41QWp0ZkpyNHAxMDF1MlNzTmNMQXI0MkE1ZnJhbjl2TDI5SGphTTJNVFU4TDBPeG9JWDhsZ2NwVXk5d2NpN2xIUUtPaXdhT2NJS2ZDQzFxTTdsTzV6MGM0UCtvMHpUNjE4M3hKVjNybXcyMkdHWWQ0MEVCcVc5N29xQkswSWorS2w1c3V5Y1o0SjJxSzFhVmNpWUJac0JObGJ0bXovazhIdUJ4eTlXYkVlUHNZLzYxSTUwZkJMU0FrVmsvVGVhNGorTk5ISjFpbXA3Qm8xOGFMbzhwbGI5ZTJpWmVJRHpIMXU2Nm8wUkZZYkhkbkpEOENuUGVCTFZnU3ZFcW1CUzExZmdIcjgxL3RrNWxKeGNLZWpkc0VmdHpHUXh3dUh3L3Bqa2pvYklreHJyb1hwYTZpWG9rVnlINGJlMTYrZi9kRGFFa2g5UmY4TGgxVUVRUHh4cEN5SVNNaWZINXBMNzhES2hHbmg4VmZpN0Vlc1VWMWs2WTNlVkNGdzJDQ0tXY3ZYc0piOVFxTEZzRHFJbFdQaDZiQmdNNGFYZnBlMGFyRHJnWVJiYng4TDZvdWh5eEFId2p0ejlpMGxYZXpXTVg1ZjdRWVJFTVRDNXlCUE5UVFAyZkNOc296UT09PC9YNTA5Q2VydGlmaWNhdGU+PC9YNTA5RGF0YT48L0tleUluZm8+PE9iamVjdD48YmFua0lkU2lnbmVkRGF0YSB4bWxucz0iaHR0cDovL3d3dy5iYW5raWQuY29tL3NpZ25hdHVyZS92MS4wLjAvdHlwZXMiIElkPSJiaWRTaWduZWREYXRhIj48dXNyVmlzaWJsZURhdGEgY2hhcnNldD0iVVRGLTgiIHZpc2libGU9Ind5c2l3eXMiPmMybG5ibVZ5YVc1bmMzUmxlSFE9PC91c3JWaXNpYmxlRGF0YT48dXNyTm9uVmlzaWJsZURhdGE+WldvZ2MzbHViR2xuPC91c3JOb25WaXNpYmxlRGF0YT48c3J2SW5mbz48bmFtZT5ZMjQ5Um1sdVlXNXphV1ZzYkNCSlJDMVVaV3R1YVdzZ1FrbEVJRUZDTEc1aGJXVTlWR1Z6ZENCaGRpQkNZVzVyU1VRc2MyVnlhV0ZzVG5WdFltVnlQVFUxTmpZek1EUTVNamdzYnoxVVpYTjBZbUZ1YXlCQklFRkNJQ2h3ZFdKc0tTeGpQVk5GPC9uYW1lPjxub25jZT50eFhnUEMvQ2VleGttbHpJMC9aUG5ZNXk2bFE9PC9ub25jZT48ZGlzcGxheU5hbWU+VkdWemRDQmhkaUJDWVc1clNVUT08L2Rpc3BsYXlOYW1lPjwvc3J2SW5mbz48Y2xpZW50SW5mbz48ZnVuY0lkPklkZW50aWZpY2F0aW9uPC9mdW5jSWQ+PHZlcnNpb24+VUdWeWMyOXVZV3c5Tnk0eE1pNHhMalVtUW1GdWEwbEVYMlY0WlQwM0xqRXlMakV1TlNaQ1NWTlFQVGN1TVRJdU1TNDFKbkJzWVhSbWIzSnRQVzFoWTI5emVDWnZjMTkyWlhKemFXOXVQVEV5TGpVbVpHbHpjR3hoZVY5MlpYSnphVzl1UFNaMWFHazlaMmxEYzFWelZsbG5Xa1ZuT1RaUUwyd3ZOMUZKZUZkcWRYTnhWeVpzWldkaFkzbDFhR2s5WjJsRGMxVnpWbGxuV2tWbk9UWlFMMnd2TjFGSmVGZHFkWE54VnlaaVpYTjBYMkpsWm05eVpUMHhOalkxTWpFM056STBKZz09PC92ZXJzaW9uPjxlbnY+PGFpPjx0eXBlPlQxTmZXQT09PC90eXBlPjxkZXZpY2VJbmZvPk1USXVOUT09PC9kZXZpY2VJbmZvPjx1aGk+Z2lDc1VzVllnWkVnOTZQL2wvN1FJeFdqdXNxVzwvdWhpPjxmc2liPjA8L2ZzaWI+PHV0Yj5jczE8L3V0Yj48cmVxdWlyZW1lbnQ+PGNvbmRpdGlvbj48dHlwZT5DZXJ0aWZpY2F0ZVBvbGljaWVzPC90eXBlPjx2YWx1ZT4xLjIuMy40LjU8L3ZhbHVlPjwvY29uZGl0aW9uPjwvcmVxdWlyZW1lbnQ+PHVhdXRoPnB3PC91YXV0aD48L2FpPjwvZW52PjwvY2xpZW50SW5mbz48L2JhbmtJZFNpZ25lZERhdGE+PC9PYmplY3Q+PC9TaWduYXR1cmU+';

    protected $ocspResponse = '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                      +7lRy28dFLMfQ6Htojcm2JhMXiruVxRT43p1VpJdPmLDwPE69YaIC6gzAN31DqkpMNHcjjOMxah2dRgvmjnLQVkP0niufbHYXDOXL4DctIbX+3zAxmj6W+jBuJMW7jMorUsdlEGdfaJeEuZrpNZ14aVcB1Qtl3fUiOkKRCTOGq8BuEb+unk99nYkn/ln10erw3TqjbfbhkzDuwgMTpbpn4GTFNthlreDIHHgUSgoQEaEZEWKA4WcgvVGqBlWmHVpB+HbvxzVZEdfc2Dke1ZiDisgmzhcN3TLQhHnndc9YiqDmTBkK47EWEggAqZcoGSDEtI9eImBMSEg=';

    protected $signatureNoVisibleData =
        'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PFNpZ25hdHVyZSB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+PFNpZ25lZEluZm8geG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiPjxDYW5vbmljYWxpemF0aW9uTWV0aG9kIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvVFIvMjAwMS9SRUMteG1sLWMxNG4tMjAwMTAzMTUiPjwvQ2Fub25pY2FsaXphdGlvbk1ldGhvZD48U2lnbmF0dXJlTWV0aG9kIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8wNC94bWxkc2lnLW1vcmUjcnNhLXNoYTI1NiI+PC9TaWduYXR1cmVNZXRob2Q+PFJlZmVyZW5jZSBUeXBlPSJodHRwOi8vd3d3LmJhbmtpZC5jb20vc2lnbmF0dXJlL3YxLjAuMC90eXBlcyIgVVJJPSIjYmlkU2lnbmVkRGF0YSI+PFRyYW5zZm9ybXM+PFRyYW5zZm9ybSBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnL1RSLzIwMDEvUkVDLXhtbC1jMTRuLTIwMDEwMzE1Ij48L1RyYW5zZm9ybT48L1RyYW5zZm9ybXM+PERpZ2VzdE1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvMDQveG1sZW5jI3NoYTI1NiI+PC9EaWdlc3RNZXRob2Q+PERpZ2VzdFZhbHVlPmZ1WGtESytCUmJ5bWhkUmxvM0FrQk5BbzlxWU1yNng1S2I0ZXpaeVl1YkE9PC9EaWdlc3RWYWx1ZT48L1JlZmVyZW5jZT48UmVmZXJlbmNlIFVSST0iI2JpZEtleUluZm8iPjxUcmFuc2Zvcm1zPjxUcmFuc2Zvcm0gQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy14bWwtYzE0bi0yMDAxMDMxNSI+PC9UcmFuc2Zvcm0+PC9UcmFuc2Zvcm1zPjxEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiPjwvRGlnZXN0TWV0aG9kPjxEaWdlc3RWYWx1ZT5jbC92cFRsRlhTTkpuQ0M0VmNudGhJN1hNNTZHaHBlZ28ycFcwS1lRc04wPTwvRGlnZXN0VmFsdWU+PC9SZWZlcmVuY2U+PC9TaWduZWRJbmZvPjxTaWduYXR1cmVWYWx1ZT55eE1GVjJYSU1SMUp2KzI2d0dDc1gwV3VheDdGRW1FVks1R0dwdlByU1hFaTh5aUdXaFJiejhGajVRQnBkWGg0MW44eTN5MHh0ZzczbEUwMEVvS25UMThSakhhY0xFZThTbEJLSTk2cDUvTnVxSXdsSjJjSmNXSGRZeGwzdmxwUGh6MGZsY0dDVlNTWUNOaHJ2QTdDNGRZZ01RYkhSMktPbHBaZHZBclF1R3FxNHNTbGVraDVvQlRWeWd5SVpQTklqRmduVi9zSS8ydmRUOXFsVW9DSWI1SmUzRGpsWlNFd3JHMU5PMHV4a1o3aEdFbVFTdmEvdlFEcDBxTXNiQ1VOOXdmVHVwbzlFbEZzRElvcjBwVDBUMFNpbE5OZC80clNsamZDRG9lSFlteGVLM2JoQ0o3a29xeVZvYUtzNjh3dVdXcWdPb1VJc2J4KzEzckpPckhxb2c9PTwvU2lnbmF0dXJlVmFsdWU+PEtleUluZm8geG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiIElkPSJiaWRLZXlJbmZvIj48WDUwOURhdGE+PFg1MDlDZXJ0aWZpY2F0ZT5NSUlGV2pDQ0EwS2dBd0lCQWdJSUZydmZTenlDaVJrd0RRWUpLb1pJaHZjTkFRRUxCUUF3ZURFTE1Ba0dBMVVFQmhNQ1UwVXhIVEFiQmdOVkJBb01GRlJsYzNSaVlXNXJJRUVnUVVJZ0tIQjFZbXdwTVJVd0V3WURWUVFGRXd3eE1URXhNVEV4TVRFeE1URXhNekF4QmdOVkJBTU1LbFJsYzNSaVlXNXJJRUVnUTNWemRHOXRaWElnUTBFeElIWXhJR1p2Y2lCQ1lXNXJTVVFnVkdWemREQWVGdzB5TWpBMk1UWXlNakF3TURCYUZ3MHlNekEyTVRjeU1UVTVOVGxhTUlHN01Rc3dDUVlEVlFRR0V3SlRSVEVkTUJzR0ExVUVDZ3dVVkdWemRHSmhibXNnUVNCQlFpQW9jSFZpYkNreEVUQVBCZ05WQkFRTUNFaEZVMU5NVlU1RU1ROHdEUVlEVlFRcURBWk5RVkpVU1U0eEZUQVRCZ05WQkFVVERERTVPRFV3TVRFek9UazVOVEU0TURZR0ExVUVLUXd2S0RJeU1EWXhOeUF3T1M0d09Da2dUVUZTVkVsT0lFaEZVMU5NVlU1RUlDMGdRbUZ1YTBsRUlIRERwU0JtYVd3eEdEQVdCZ05WQkFNTUQwMUJVbFJKVGlCSVJWTlRURlZPUkRDQ0FTSXdEUVlKS29aSWh2Y05BUUVCQlFBRGdnRVBBRENDQVFvQ2dnRUJBTldZNHIwL296MlBSbmRSTGxVeFI1OG5WOTBzSkV2c1dmM3VuVnZob1RrK0dxbmE5a1N6VFIyL0tJN0t6amZXcGxtTzFvK21XbEVNMVM5ODVMay9PZk5yRkFUKzI1ekZreHdxSVNYeUl2MTErNXFWM1B2NTBIbGFGRHplTEZUaXNmNWF6bE00cU9EaFNzRzY2cHp3endBbCs4S0dsUVBWczFkSmZCdWhoSE14RW5JRDMwaXdpNDRsUUljSU5Bci8rUVRkSVBqbWJ5eDZoMVVqL2lIMmFNWjgrQjBONWlMZVg5U3JRUXMybHZXWW5hOVIwVlpHSnZBV2ZqV2oxQnlqU0EweVJvR1JtUGtvVFl1KzRjK3VhTWo4aGFPaGtJbWNLeU5heWNOc00wZVIxMWNJUVNrOGlwZ09URVFZdzdGVWg5NjhMT2U5RXl3N1RaWXZLS3hscGQ4Q0F3RUFBYU9Cb3pDQm9EQTdCZ2dyQmdFRkJRY0JBUVF2TUMwd0t3WUlLd1lCQlFVSE1BR0dIMmgwZEhBNkx5OTBaWE4wTG5KbGRtOWpZWFJwYjI1emRHRjBkWE11YzJVd0VRWURWUjBnQkFvd0NEQUdCZ1FxQXdRRk1BNEdBMVVkRHdFQi93UUVBd0lIZ0RBZkJnTlZIU01FR0RBV2dCUmdlbjJuV1lPTW42U3hGK29OUTBPVlErYVovVEFkQmdOVkhRNEVGZ1FVVm5Dd3J0NjVaV3BKMmM3dTVoUDh4clVYZ0Q4d0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dJQkFDZWIycmVDL0hOcW5OaGtnWUZRMGtjN3UvRnQ4ck9FRzJ5WHAwTk5qb1JxRzhxMC9kUHNiR1ZsQnBsV0dNM0xiRHc3WVhBRGZXTmxlNWpId2lrcDZBa2lST3JmWjJTK0FsakpsUmVyRElReUJTYVJOck1UQWRzSjFFSVk3QytDQWhpeHVTMnN2ZEdzMjVaZzBXam43d0tVNWZKUUVqdzdwQS9rN0t0S3NQL001NGJOdkFBaWc0L1RGWTNkV2llL1BsVXMrUVVRZFU3TTIyUkxheXlsVHVPOW1WbWZPdkxJWjFRYjVxeGltd0diUlczOWVlRVQzZUFsSFhXZGN3NjQ0a055RjRmQ1BqOFJFYk52ejNrS2duNWdicHBVRE92N1VhR3RKdE8rMUdYNGZlSjZGRVlEWUZ2NERrMkcwOWFTZzdpSTMxQVBEYlFIcURZWTlONHk5MmsySzZWdGwxOGQ4eThYYk0rYnN3ZDkrZXQxMm5XUjhnNERMcUMzOXkyb2c0MVozeWYyM3pDcU8xeTZCcHVnbXBsYzNReDQ0R0pyWGE0bndhUEtvS2FNRkxvZG5tUXJ4WTdzTmZnU04vUitCMzFDOVA5ckdHZnFMRWlDNzJ5ZVZlN3FqSWMvSkdXcTdGMHF3WXhHVGI4R1dDOUFJRnZvMDNWYWI3dk95aHBDeFRDbzVYUnZoU0hQZlEzeElEcDRBcnZEdU0wSk9sYURRcTRNSkVrZ0t4Z2FNdWUrNWtCMktRcFo4VHdUTE1Ta3N3aTFBclZ5Rm1YMG52c1ZBS1RQVi9tVTExVUYzMWZNL3JGeUFIbDdybU90MnNMSU9CYnd3bTVjUVcyKy9rU0lVNGpUMVFjam0yUzVZZWY2bGtoaUpqb1ArZDR3N09SZ2VzQTlDTVdXYlVxTDwvWDUwOUNlcnRpZmljYXRlPjxYNTA5Q2VydGlmaWNhdGU+TUlJRjNqQ0NBOGFnQXdJQkFnSUlGblpWeWVobVhZd3dEUVlKS29aSWh2Y05BUUVOQlFBd2JqRUxNQWtHQTFVRUJoTUNVMFV4SFRBYkJnTlZCQW9NRkZSbGMzUmlZVzVySUVFZ1FVSWdLSEIxWW13cE1SVXdFd1lEVlFRRkV3d3hNVEV4TVRFeE1URXhNVEV4S1RBbkJnTlZCQU1NSUZSbGMzUmlZVzVySUVFZ1EwRWdkakVnWm05eUlFSmhibXRKUkNCVVpYTjBNQjRYRFRFeE1Ea3lNakUwTWpFeE5Gb1hEVE0wTVRJd01URTBNakV4TkZvd2VERUxNQWtHQTFVRUJoTUNVMFV4SFRBYkJnTlZCQW9NRkZSbGMzUmlZVzVySUVFZ1FVSWdLSEIxWW13cE1SVXdFd1lEVlFRRkV3d3hNVEV4TVRFeE1URXhNVEV4TXpBeEJnTlZCQU1NS2xSbGMzUmlZVzVySUVFZ1EzVnpkRzl0WlhJZ1EwRXhJSFl4SUdadmNpQkNZVzVyU1VRZ1ZHVnpkRENDQWlJd0RRWUpLb1pJaHZjTkFRRUJCUUFEZ2dJUEFEQ0NBZ29DZ2dJQkFJVzBEUG9wTEVodGF3VlJ3TnJFNDMxR1ZzaC9IbldWc1hkZ09qelVzRDdRRDMwL3RmT0hST1FpOW5MdURXa1kxZkVVeFowNllxNUx0Uk9vRnBrVFE2U1JpMlJnaVVrdUNOcU1Fd3NqMmVpYTdLaFlSSWsvWEpra0ZwMUJ2RTYySTYzdnRVelp6UzY5SEFzTU5QbGZkTFUycElaMkFuZDJRSjJkQzB4aW1tRmpZNWs1L3o3L05rM0pHQmJheExIL1g2emhjTnFPcHIyU3J2OUcrbGsrR3Z5N2hRTEltTkxSVis0RzNtYWxIajZRTSt3RGNSS3ZUNFYraVJkdnpQOW84MDMvZytHTDVxaXVmVzZSZFQrMmx3R2lmUDJkM3N1TDc5dUdXMUhPOHFiaWkvaTRIVHhEZnRLZFhGc2xGclhmUisrUVVVNEIrdjZReWI0ckYzcWhEZmVha2dmTDh1emZ0TXRUTVJsb3d4SWIwOGp4Q2VoQ1NhWTBDTUJIUVRTMEx0WDFDL1ZqTTZVYmJwU2EyODB6U0wreFhsUzdTNzI3c0pCNzIyZnpXUjMvTlNwM01aVGJFMFFBcU1URU5ZNHBmd2MvbFh3Vm44VHZBTncxRkl4RTdpa3dJQk1GU282ZVgyVUREejlhaTZkelJyWWZ0STQ0RXRMVHYzS1Y1VURXY0lic1JCdmxnQlFxcXVwaGN1UlZ2MWE2WG85eGVIMitvK0Jzcitzb3VtaUM2eklGdVV1QnhCNHVxc1NxZVZRRmtJYWVwaW53aFg1Q0pCWkxjT1JhTVpGNkkxa0d2RURaT1ZZWE9FdDlQV2cvU3NTY0dNK3NmMjUxMEd6MGYyb21Rak9MNUJlemRZWUtOQXd6aXo5VTFJcjFWcHZ6a0pGNFNBM1cwNWNtampLWkFnTUJBQUdqZGpCME1CMEdBMVVkRGdRV0JCUmdlbjJuV1lPTW42U3hGK29OUTBPVlErYVovVEFQQmdOVkhSTUJBZjhFQlRBREFRSC9NQjhHQTFVZEl3UVlNQmFBRktQeWVIa2RLMFdLeWVIS2xRbmxubS9PeTA3Rk1CRUdBMVVkSUFRS01BZ3dCZ1lFS2dNRUJUQU9CZ05WSFE4QkFmOEVCQU1DQVFZd0RRWUpLb1pJaHZjTkFRRU5CUUFEZ2dJQkFEeGh5eldTem9reUcraFVDcDNVZzdRWnhiTUxLKzZJWXArOGFjUnVUU0ZmcjVtYUgzTXJ5ZDg3L0IyeTlLM2ZXK0ZYUUxwZGhWSG92S0pPQVF5di90M0NBNjJaR3J6aEFYR3FDY1I5U240NGVjS1JKUEU5WkpiemFsbzR3dEtSVXYwNFcyWmdGdW5ZVE41NVRzTm4zYkd6Y0lpQWRkTXE5VE1Ld0lqbDZwNWk2b0lqQW10OS83NVFmN3FRLzF4MjBFVWRzdis4UVBJcDF2bEI4dkF6QXRvKzhiWkZDUnNkTVZMUlJrOTZDb1M1M3Y0YURZWUFNeG1zVGJndkxxVlU1L0NOZlZFZ1ZlU3BGVlN6NmZsYkZNQlpkNUxPUGdsaS9sUko3Rldld1F2clphS2dmSmdkbVVVdkNwaTBlRCsvS0Juc0VKTGJoZG5LL0IraVRvNEE2QndvUis5WGhPUXlOTVRCL1NEdFNZY3pKMzV2RmhaZktKNS8wcHNxWFNKSC8yNXdBNHBlLzM0RVJ6UTFtZ2xhZHQ2Sk9obldmOTJKdzVqZHc3QkZwdGc3bG1Ja0R5WURVKzZSeUVzQXJDaWJJKzI4eUY1L2ZDWkN1VWR3RHc5aUhwb29kZjFoOHQxZ2ZQbm5ta2N3R1RmUGcvZHVVZ2tGd0tZOTdTemZaZ1IwMmhkN3h4bzVwSzc5Y3ppbU1GMkdURnc5U1dTbmxaSzcxZm9ZMjVGelNVSE5tdUdIaEZ6Rzk4QUZJdDBWTHdpVGo4dEplU2pUaTQxaWYyMzd2RE52c2VwdCs4L3R0ODAvZjQ1S3pQTmZXVUIwNi9GR3Iwd2ZvWWdacDRQaTlSUlRYekRhZndqN3FMZHVhZXBSckxjRVVwWFdDR3J1U1V5bHh4Q2hkQlR3VnpabjwvWDUwOUNlcnRpZmljYXRlPjxYNTA5Q2VydGlmaWNhdGU+TUlJRjB6Q0NBN3VnQXdJQkFnSUlVWW1mZHRxdHk4MHdEUVlKS29aSWh2Y05BUUVOQlFBd2JURWtNQ0lHQTFVRUNnd2JSbWx1WVc1emFXVnNiQ0JKUkMxVVpXdHVhV3NnUWtsRUlFRkNNUjh3SFFZRFZRUUxEQlpDWVc1clNVUWdUV1Z0WW1WeUlFSmhibXR6SUVOQk1TUXdJZ1lEVlFRRERCdFVaWE4wSUVKaGJtdEpSQ0JTYjI5MElFTkJJSFl4SUZSbGMzUXdIaGNOTVRFd09USXlNVFF4TlRBeldoY05NelF4TWpNeE1UUXdNVE16V2pCdU1Rc3dDUVlEVlFRR0V3SlRSVEVkTUJzR0ExVUVDZ3dVVkdWemRHSmhibXNnUVNCQlFpQW9jSFZpYkNreEZUQVRCZ05WQkFVVERERXhNVEV4TVRFeE1URXhNVEVwTUNjR0ExVUVBd3dnVkdWemRHSmhibXNnUVNCRFFTQjJNU0JtYjNJZ1FtRnVhMGxFSUZSbGMzUXdnZ0lpTUEwR0NTcUdTSWIzRFFFQkFRVUFBNElDRHdBd2dnSUtBb0lDQVFDVHFVN3V4azVRemJYUzZBclhJR1RXTmVaWHo2NWJ6ZGdveGI3OUx2WWgvcDdrY0syNW1BMnR6R3BPM1FTMWVLSkp1ODRHOVVOem00bU1sNmNuZ25YY2p4RVRZaUVxdGlqckE1bWZ6ODY1L1g2VWdPcFg3RGtvdVE4ZDVlRHloSjQ5VXJEcWxyZ29WTXgzMjJrTTBTWjRoZVZlWDgzZTFJU0ZpeXhxWkJLeGgyNXlLWUVaQTRFeklyRGoydGk4Q1JyV1BIQ1RXYUlGcGNkNVR5TWhwVVRQbjREendQaFBHV01STnhnT0FlUDRCU0RCN1I2YXo0cm94N1RQa2Qyc1dHMU9Eai8wSVJQaEpTMWRRMUI3UWlOSFk1OFJqbk5UaEVRS3dkV1dNUE1LUHRoU2QrR0VqTDlHRGFmWXhPc0lyS0ZZd2xZTkJXM0M1bWJlM1QrM2orQXhqNlcySGJnbUpYUEdJdEx1Y3hZMWtQd1Q5TDd1NW5JeGFST21oMXVUd1lxcjlwdUdxNnNvSm5nZ0VTM0s0UEloTTZrYW12bkNDUFhvcVdDQ3J1U0VQVmd5RVpFaTBzaHkrODFRc2ViMWdjOXJZZ1ZyRW5MQk9JeU1xYVR0RXhhRnByWWJ2MWYvQXdXdGpGVWkyWGlTZE44YU1wK2txYmkrMXRLSlVVUExDK0NyZHU5ZkZvLzhsc2xTZGV3K1NuUFZGZVZ6NUNPS2J0NkdURTR4Y0plUnpXNXdRMHc3YityR0xXaEp2d1JKc1M1R1h2cWEzTGc4RXlXaUxKc3d1VEZhRXdQVUR2WkJ2eUZaRVplcnRLZ1piUll2ZXpvOS9ncnd5Qittb3JWckxyeXU5Y2hZRVl3RTU1MHV6eUt0elhVenlnVjhGcFhlOURwbXBPU2ZHTUFVUlFJREFRQUJvM1l3ZERBZEJnTlZIUTRFRmdRVW8vSjRlUjByUllySjRjcVZDZVdlYjg3TFRzVXdEd1lEVlIwVEFRSC9CQVV3QXdFQi96QWZCZ05WSFNNRUdEQVdnQlJLOTZOcUNOb0lPQmNaVXlqSTJxYldOTmhhdWpBUkJnTlZIU0FFQ2pBSU1BWUdCQ29EQkFVd0RnWURWUjBQQVFIL0JBUURBZ0VHTUEwR0NTcUdTSWIzRFFFQkRRVUFBNElDQVFEUDFEb3hqRWpleUcyN3hlYWkrbXB4eEpvcUIxUkRWVEVZODZSZE55bHVVS1FPSWJmS0pNbVgrRFg0dlR1VVFTMzUzOXh6SEt3cGo2Z2sraVpWakYxVW9KdEdwK3F1cmpqYXJPaDQ0cysrczB5V0tpS3JKQkVsb0puOG8rWVhGVDhDN2UxV3RxSlZvYUZkREJDdm9oSnlLMjBQS1M3L25VRzViN0o2aXEzNTE3WXZqYjREOTRMdDBkSE5TZ0QyQklJSG1Oa3BTWVdneWkxc2VhdmhONUFqdGZKcjRwMTAxdTJTc05jTEFyNDJBNWZyYW45dkwyOUhqYU0yTVRVOEwwT3hvSVg4bGdjcFV5OXdjaTdsSFFLT2l3YU9jSUtmQ0MxcU03bE81ejBjNFArbzB6VDYxODN4SlYzcm13MjJHR1lkNDBFQnFXOTdvcUJLMElqK0tsNXN1eWNaNEoycUsxYVZjaVlCWnNCTmxidG16L2s4SHVCeHk5V2JFZVBzWS82MUk1MGZCTFNBa1ZrL1RlYTRqK05OSEoxaW1wN0JvMThhTG84cGxiOWUyaVplSUR6SDF1NjZvMFJGWWJIZG5KRDhDblBlQkxWZ1N2RXFtQlMxMWZnSHI4MS90azVsSnhjS2VqZHNFZnR6R1F4d3VIdy9wamtqb2JJa3hycm9YcGE2aVhva1Z5SDRiZTE2K2YvZERhRWtoOVJmOExoMVVFUVB4eHBDeUlTTWlmSDVwTDc4REtoR25oOFZmaTdFZXNVVjFrNlkzZVZDRncyQ0NLV2N2WHNKYjlRcUxGc0RxSWxXUGg2YkJnTTRhWGZwZTBhckRyZ1lSYmJ4OEw2b3VoeXhBSHdqdHo5aTBsWGV6V01YNWY3UVlSRU1UQzV5QlBOVFRQMmZDTnNvelE9PTwvWDUwOUNlcnRpZmljYXRlPjwvWDUwOURhdGE+PC9LZXlJbmZvPjxPYmplY3Q+PGJhbmtJZFNpZ25lZERhdGEgeG1sbnM9Imh0dHA6Ly93d3cuYmFua2lkLmNvbS9zaWduYXR1cmUvdjEuMC4wL3R5cGVzIiBJZD0iYmlkU2lnbmVkRGF0YSI+PHNydkluZm8+PG5hbWU+WTI0OVJsQWdWR1Z6ZEdObGNuUWdOQ3h1WVcxbFBWUmxjM1FnWVhZZ1FtRnVhMGxFTEhObGNtbGhiRTUxYldKbGNqMDFOVFkyTXpBME9USTRMRzg5VkdWemRHSmhibXNnUVNCQlFpQW9jSFZpYkNrc1l6MVRSUT09PC9uYW1lPjxub25jZT43RTZHVUZrM2RXbVd0Rk1xR05IYjZQSGdNalU9PC9ub25jZT48ZGlzcGxheU5hbWU+VkdWemRDQmhkaUJDWVc1clNVUT08L2Rpc3BsYXlOYW1lPjwvc3J2SW5mbz48Y2xpZW50SW5mbz48ZnVuY0lkPklkZW50aWZpY2F0aW9uPC9mdW5jSWQ+PHZlcnNpb24+VUdWeWMyOXVZV3c5Tnk0eE1pNHhMalVtUW1GdWEwbEVYMlY0WlQwM0xqRXlMakV1TlNaQ1NWTlFQVGN1TVRJdU1TNDFKbkJzWVhSbWIzSnRQVzFoWTI5emVDWnZjMTkyWlhKemFXOXVQVEV5TGpZbVpHbHpjR3hoZVY5MlpYSnphVzl1UFNaMWFHazlaM0F2V1hoelduTlhXbXBHYlhST1JqRkRabVJWWVVweFpESjVieVpzWldkaFkzbDFhR2s5WjNBdldYaHpXbk5YV21wR2JYUk9SakZEWm1SVllVcHhaREo1YnlaaVpYTjBYMkpsWm05eVpUMHhOalkxT0RJM09ERTRKZz09PC92ZXJzaW9uPjxlbnY+PGFpPjx0eXBlPlQxTmZXQT09PC90eXBlPjxkZXZpY2VJbmZvPk1USXVOZz09PC9kZXZpY2VJbmZvPjx1aGk+Z3AvWXhzWnNXWmpGbXRORjFDZmRVYUpxZDJ5bzwvdWhpPjxmc2liPjA8L2ZzaWI+PHV0Yj5jczE8L3V0Yj48cmVxdWlyZW1lbnQ+PGNvbmRpdGlvbj48dHlwZT5DZXJ0aWZpY2F0ZVBvbGljaWVzPC90eXBlPjx2YWx1ZT4xLjIuMy40LjU8L3ZhbHVlPjwvY29uZGl0aW9uPjxjb25kaXRpb24+PHR5cGU+VG9rZW5TdGFydFJlcXVpcmVkPC90eXBlPjx2YWx1ZT55ZXM8L3ZhbHVlPjwvY29uZGl0aW9uPjwvcmVxdWlyZW1lbnQ+PHVhdXRoPnB3PC91YXV0aD48L2FpPjwvZW52PjwvY2xpZW50SW5mbz48L2JhbmtJZFNpZ25lZERhdGE+PC9PYmplY3Q+PC9TaWduYXR1cmU+';

    protected $ocspNoVisibleData = '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';

    protected BankIDService $service;

    protected $rpApiMock;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'bankid.use_environment' => 'test',
        ]);

        $this->rpApiMock = $this->mock(RpApi::class);
        $this->app->instance(RpApi::class, $this->rpApiMock);

        $this->service = $this->app->make(BankIDService::class);
    }

    public function test_auth()
    {
        $startTransactionResponse = new StartTransactionResponse();
        $startTransactionResponse->setAutoStartToken('autoStart');
        $startTransactionResponse->setOrderRef('orderRef');

        $this->rpApiMock->shouldReceive('auth')
            ->once()
            ->andReturn($startTransactionResponse);

        $transactionResponse = $this->service->auth(self::CLIENT_IP);

        $this->assertEquals('autoStart', $transactionResponse->getAutoStartToken());

        $sessionTransaction = $this->service->getSessionTransaction();
        $this->assertEquals('orderRef', $sessionTransaction->getOrderRef());
        $this->assertEquals('autoStart', $sessionTransaction->getAutoStartToken());
    }

    public function test_auth_failed()
    {
        $this->rpApiMock->shouldReceive('auth')
            ->once()
            ->andReturn(null);

        $transactionResponse = $this->service->auth(self::CLIENT_IP);
        $this->assertNull($transactionResponse);
        $this->assertNull($this->service->getSessionTransaction());
    }

    public function test_sign()
    {
        $startTransactionResponse = new StartTransactionResponse();
        $startTransactionResponse->setAutoStartToken('autoStart');
        $startTransactionResponse->setOrderRef('orderRef');

        $this->rpApiMock->shouldReceive('sign')
            ->once()
            ->andReturn($startTransactionResponse);

        $transactionResponse = $this->service->sign(self::CLIENT_IP, 'Sign this data');

        $this->assertEquals('autoStart', $transactionResponse->getAutoStartToken());

        $sessionTransaction = $this->service->getSessionTransaction();
        $this->assertEquals('orderRef', $sessionTransaction->getOrderRef());
        $this->assertEquals('autoStart', $sessionTransaction->getAutoStartToken());
    }

    public function test_sign_failed()
    {
        $this->rpApiMock->shouldReceive('sign')
            ->once()
            ->andReturn(null);

        $transactionResponse = $this->service->sign(self::CLIENT_IP, 'Sign this data');
        $this->assertNull($transactionResponse);
        $this->assertNull($this->service->getSessionTransaction());
    }

    public function test_collect_pending()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(self::FAKE_TIME));

        $collectResponse = new CollectResponse();
        $collectResponse->setOrderRef('orderInfo123');
        $collectResponse->setStatus(Status::PENDING);
        $collectResponse->setHintCode('outstandingTransaction');

        $this->rpApiMock->shouldReceive('collect')
            ->andReturn($collectResponse);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        // Assert first collect
        $this->assertEquals('outstandingTransaction', $response->getHintCode());
        $this->assertEquals(Status::PENDING, $response->getStatus());
        $this->assertEquals(
            "bankid.67df3917-fa0d-44e5-b327-edcc928297f8.0.dc69358e712458a66a7525beef148ae8526b1c71610eff2c16cdffb4cdac9bf8",
            $response->getQrCode()
        );

        // Fake 1-second sleep
        Carbon::setTestNow(Carbon::createFromTimestamp(self::FAKE_TIME + 1));

        // Call collect
        $response = $this->service->collect();

        // Assert second collect
        $this->assertEquals("outstandingTransaction", $response->getHintCode());
        $this->assertEquals(Status::PENDING, $response->getStatus());
        $this->assertEquals(
            "bankid.67df3917-fa0d-44e5-b327-edcc928297f8.1.949d559bf23403952a94d103e67743126381eda00f0b3cbddbf7c96b1adcbce2",
            $response->getQrCode());

        // Verify only one collect
        $this->rpApiMock->shouldHaveReceived('collect')->once();

        // Fake 1-second sleep
        Carbon::setTestNow(Carbon::createFromTimestamp(self::FAKE_TIME + 2));

        // Call collect
        $response = $this->service->collect();
        $this->assertEquals("outstandingTransaction", $response->getHintCode());
        $this->assertEquals(Status::PENDING, $response->getStatus());
        $this->assertEquals(
            "bankid.67df3917-fa0d-44e5-b327-edcc928297f8.2.a9e5ec59cb4eee4ef4117150abc58fad7a85439a6a96ccbecc3668b41795b3f3",
            $response->getQrCode());
        $this->assertNull($response->getCollectResult()->getCompletionResult());

        $this->rpApiMock->shouldHaveReceived('collect')->twice();
    }

    public function test_collect_returns_null()
    {
        $this->rpApiMock->shouldReceive('collect')
            ->andReturn(null);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        $this->assertNull($response);
        $this->assertNull($this->service->getSessionTransaction());
    }

    public function test_collect_pending_no_qr_code()
    {
        $collectResponse = new CollectResponse();
        $collectResponse->setOrderRef('orderInfo123');
        $collectResponse->setStatus(Status::PENDING);
        $collectResponse->setHintCode('started');

        $this->rpApiMock->shouldReceive('collect')
            ->andReturn($collectResponse);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        $this->assertEquals(Status::PENDING, $response->getStatus());
        $this->assertEquals('started', $response->getHintCode());
        $this->assertNull($response->getQrCode());
    }

    public function test_collect_failed()
    {
        $collectResponse = new CollectResponse();
        $collectResponse->setOrderRef('orderInfo123');
        $collectResponse->setStatus(Status::FAILED);
        $collectResponse->setHintCode('startFailed');

        $this->rpApiMock->shouldReceive('collect')
            ->andReturn($collectResponse);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        $this->assertEquals(Status::FAILED, $response->getStatus());
        $this->assertEquals('startFailed', $response->getHintCode());
        $this->assertNull($response->getQrCode());
    }

    public function test_collect_complete()
    {
        $userData = new UserData();
        $userData->setName('Test Name');
        $userData->setPersonalNumber('123456789012');
        $completionData = new CompletionData();
        $completionData->setUser($userData);
        $completionData->setSignature($this->signature);
        $completionData->setOcspResponse($this->ocspResponse);

        $collectResponse = new CollectResponse();
        $collectResponse->setOrderRef('orderInfo123');
        $collectResponse->setStatus(Status::COMPLETE);
        $collectResponse->setCompletionData($completionData);

        $this->rpApiMock->shouldReceive('collect')
            ->andReturn($collectResponse);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        $collectResult = $response->getCollectResult();

        $this->assertEquals(Status::COMPLETE, $response->getStatus());
        $this->assertNull($response->getQrCode());
        $this->assertNull($response->getHintCode());
        $this->assertNotNull($collectResult->getCompletionResult());
        $this->assertEquals('Test Name', $collectResult->getCompletionResult()->getName());
        $this->assertEquals('123456789012', $collectResult->getCompletionResult()->getPersonalNumber());
        $this->assertEquals('signeringstext', $collectResult->getCompletionResult()->getSignedText());
    }

    public function test_collect_no_visible_data()
    {
        $userData = new UserData();
        $userData->setName('Test Name');
        $userData->setPersonalNumber('123456789012');
        $completionData = new CompletionData();
        $completionData->setUser($userData);
        $completionData->setSignature($this->signatureNoVisibleData);
        $completionData->setOcspResponse($this->ocspNoVisibleData);

        $collectResponse = new CollectResponse();
        $collectResponse->setOrderRef('orderInfo123');
        $collectResponse->setStatus(Status::COMPLETE);
        $collectResponse->setCompletionData($completionData);

        $this->rpApiMock->shouldReceive('collect')
            ->andReturn($collectResponse);

        $transaction = new BankIDTransaction(
            'orderInfo123',
            '67df3917-fa0d-44e5-b327-edcc928297f8',
            'd28db9a7-4cde-429e-a983-359be676944c',
            'autoStartToken'
        );

        $this->service->setSessionTransaction($transaction);

        $response = $this->service->collect();

        $collectResult = $response->getCollectResult();

        $this->assertEquals(Status::COMPLETE, $response->getStatus());
        $this->assertNull($response->getQrCode());
        $this->assertNull($response->getHintCode());
        $this->assertNotNull($collectResult->getCompletionResult());
        $this->assertEquals('Test Name', $collectResult->getCompletionResult()->getName());
        $this->assertEquals('123456789012', $collectResult->getCompletionResult()->getPersonalNumber());
        $this->assertNull($collectResult->getCompletionResult()->getSignedText());
    }
}
