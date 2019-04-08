
# perl version of bulkImportAlephseq.php
# sample usage:
# perl bulkImportAlephseq.pl fixesBias.txt < input/2records.seq
#

use Data::Walk;
use Data::GUID;
use Data::Dump qw(dump);
use Encode qw(encode_utf8);
use HTTP::Request();
use JSON::MaybeXS qw(encode_json);
use JSON::MaybeXS qw(decode_json);
use Data::Format::Pretty::JSON qw(format_pretty);
use LWP::UserAgent;
# use LWP::ConsoleLogger::Easy qw(debug_ua);
use Catmandu;
use Storable qw(dclone);
use strict;
use warnings;

my $ua = LWP::UserAgent->new;

my $req;
my $res;

# debug_ua($ua);
$ua->agent("MyApp/0.1");

my $okapiurl = 'http://172.31.1.52:9130';
my $tenant = 'diku';
my $password = 'admin';
my $username = 'diku_admin';

$req = HTTP::Request->new(
 'POST',
 "$okapiurl/authn/login",
 [ 'X-Okapi-Tenant' => $tenant,
   'Content-Type' => 'application/json; charset=UTF-8',
   'Accept' => 'application/json' ],
 encode_utf8(encode_json({ 'username' => $username, 'password' => $password }))
);

$res = $ua->request($req);
die "error: ".$res->status_line."\n" unless $res->is_success;

my $token = $res->header('X-Okapi-Token');

$req = HTTP::Request->new(
  'GET',
  "$okapiurl/locations?limit=2000",
  ['X-Okapi-Tenant' => $tenant,
  'Content-Type' => 'application/json; charset=UTF-8',
  'Accept' => 'application/json',
  'X-Okapi-Token' => $token ]
);

$res = $ua->request($req);
die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

my $locations = decode_json($res->content);

my $holdings = {};

my $importer = Catmandu->importer('MARC', 'type' => 'ALEPHSEQ');
my $seqfix = Catmandu->fixer($ARGV[0]);
my $mijfix = Catmandu->fixer('remove_field(_id)','marc_remove(FMT)','marc_in_json()');

$importer->each( sub {

  my $data = shift;

  my $mijd = dclone $data;

  $seqfix->fix($data);

  my $instanceID = $data->{'id'};

  $req = HTTP::Request->new(
    'POST',
    "$okapiurl/instance-storage/instances",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_json($data)
  );

  $res = $ua->request($req);
  die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

  $mijfix->fix($mijd);

  $req = HTTP::Request->new(
    'PUT',
    "$okapiurl/instance-storage/instances/$instanceID/source-record/marc-json",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_utf8(encode_json($mijd))
  );

  $res = $ua->request($req);
  die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

  foreach ( map {exists($_->{'Z30'}) ? $_->{'Z30'} : ()} @{$mijd->{'fields'}} ) {
    foreach ( @{$_->{'subfields'}} ){
      if( exists($_->{'1'}) && !exists($holdings->{$_->{'1'}.'-'.$instanceID}) ){
        my $bibcode = $_->{'1'};
        $holdings->{$bibcode.'-'.$instanceID}->{'id'} = Data::GUID->new->as_string;
        $holdings->{$bibcode.'-'.$instanceID}->{'instanceId'} = $instanceID;
        $holdings->{$bibcode.'-'.$instanceID}->{'permanentLocationId'} = (map { $_->{'code'} eq $bibcode ? $_->{'id'} : () } @{$locations->{'locations'}})[0];

        $req = HTTP::Request->new(
          'PUT',
          "$okapiurl/holdings-storage/holdings/".$holdings->{$bibcode.'-'.$instanceID}->{'id'},
          ['X-Okapi-Tenant' => $tenant,
          'Content-Type' => 'application/json; charset=UTF-8',
          'Accept' => 'text/plain',
          'X-Okapi-Token' => $token ],
          encode_utf8(encode_json($holdings->{$bibcode.'-'.$instanceID}))
        );
        $res = $ua->request($req);
        die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;
      }
    }

    my $item = {};
    
    $item->{'id'} = Data::GUID->new->as_string;
    $item->{'materialTypeId'} = '1a54b431-2e4f-452d-9cae-9cee66c9a892';
    $item->{'permanentLoanTypeId'} = '2b94c631-fca9-4892-a730-03ee529ffe27';
    $item->{'status'}->{'name'} = 'Available';
    foreach ( @{$_->{'subfields'}} ){
      if(exists($_->{'1'})){
        $item->{'holdingsRecordId'} = $holdings->{$_->{'1'}.'-'.$instanceID}->{'id'};
      }
      if(exists($_->{'3'})){
        $item->{'itemLevelCallNumber'} = $_->{'3'};
      }
      if(exists($_->{'5'})){
        $item->{'barcode'} = $_->{'5'};
      }
    }

    $req = HTTP::Request->new(
      'PUT',
      "$okapiurl/item-storage/items/".$item->{'id'},
      ['X-Okapi-Tenant' => $tenant,
      'Content-Type' => 'application/json; charset=UTF-8',
      'Accept' => 'text/plain',
      'X-Okapi-Token' => $token ],
      encode_utf8(encode_json($item))
    );
    $res = $ua->request($req);
    die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

    undef $item;
    
  }

});
